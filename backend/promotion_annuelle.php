<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Sécurité : uniquement le directeur
ensure_post_request();
require_admin_access(['director']);

// Assurer l'existence des tables nécessaires
ensure_students_table();
ensure_grades_table();
ensure_promotions_table();
ensure_historique_scolaire_table();
ensure_classes_table();
ensure_affectations_classes_table();

$action = clean_input((string) ($_POST['action'] ?? ''));

// ============================================================
// CARTE DES NIVEAUX : niveau actuel => niveau suivant
// ============================================================
function get_level_map(): array
{
    return [
        // Collège
        '1AC'  => '2AC',
        '2AC'  => '3AC',
        '3AC'  => 'TC',    // Passage au lycée

        // Tronc commun
        'TC'   => '1BAC',

        // Baccalauréat
        '1BAC' => '2BAC',
        '2BAC' => null,     // Diplômé / Fin de cycle
    ];
}

/**
 * Normalise le niveau depuis class_name ou level du student
 * Exemples : "2BAC A" => "2BAC", "1AC-2" => "1AC", "3AC" => "3AC"
 */
function normalize_level(string $className, string $level): string
{
    // Essayer d'abord d'extraire le niveau depuis class_name
    $cn = strtoupper(trim($className));
    $patterns = [
        '/^(2BAC|1BAC|TC|3AC|2AC|1AC)\b/i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $cn, $m)) {
            return strtoupper($m[1]);
        }
    }

    // Sinon, essayer depuis le champ level
    $lv = strtoupper(trim($level));
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $lv, $m)) {
            return strtoupper($m[1]);
        }
    }

    // Mapper les noms complets vers les codes
    $levelMap = [
        'COLLÈGE'      => '1AC',
        'COLLEGE'      => '1AC',
        'LYCÉE'        => 'TC',
        'LYCEE'        => 'TC',
        'BACCALAURÉAT' => '1BAC',
        'BACCALAUREAT' => '1BAC',
    ];

    if (isset($levelMap[$lv])) {
        return $levelMap[$lv];
    }

    // Retourner le level tel quel si rien ne matche
    return $lv !== '' ? $lv : $cn;
}

/**
 * Calculer la moyenne pondérée d'un semestre pour un étudiant
 */
function calculate_semester_average(int $studentId, string $semester): ?float
{
    $grades = db_query(
        'SELECT continuous_score, exam_score, coefficient
         FROM grades
         WHERE student_id = :sid AND semester = :sem',
        ['sid' => $studentId, 'sem' => $semester]
    )->fetchAll();

    if (empty($grades)) {
        return null;
    }

    $totalWeighted = 0.0;
    $totalCoeff = 0.0;

    foreach ($grades as $g) {
        $noteMatiere = ((float) $g['continuous_score'] + (float) $g['exam_score']) / 2;
        $coeff = (float) $g['coefficient'];
        $totalWeighted += $noteMatiere * $coeff;
        $totalCoeff += $coeff;
    }

    if ($totalCoeff <= 0) {
        return null;
    }

    return round($totalWeighted / $totalCoeff, 2);
}

/**
 * Générer le nom de classe : "2BAC A", "2BAC B", etc.
 */
function generate_class_name(string $level, int $index): string
{
    $letters = range('A', 'Z');
    $letter = $letters[$index] ?? ('G' . ($index + 1));
    return $level . ' ' . $letter;
}

// ============================================================
// ACTION: Aperçu avant lancement (preview)
// ============================================================
if ($action === 'preview') {
    $pdo = db_connection();

    // Déterminer l'année scolaire actuelle
    $y = (int) date('Y');
    $m = (int) date('m');
    // Si on est entre septembre et décembre, l'année scolaire est Y/(Y+1)
    // Sinon (janvier-août), c'est (Y-1)/Y
    if ($m >= 9) {
        $anneeCourante = $y . '-' . ($y + 1);
    } else {
        $anneeCourante = ($y - 1) . '-' . $y;
    }

    // Récupérer tous les étudiants actifs
    $students = db_query(
        "SELECT id, student_code, first_name, last_name, class_name, level
         FROM students
         WHERE registration_status IN ('registered', 'active')
         ORDER BY level, class_name, last_name"
    )->fetchAll();

    $levelMap = get_level_map();
    $preview = [];
    $statsPerLevel = [];

    foreach ($students as $s) {
        $sid = (int) $s['id'];
        $normalizedLevel = normalize_level($s['class_name'], $s['level']);

        $moyS1 = calculate_semester_average($sid, 'S1');
        $moyS2 = calculate_semester_average($sid, 'S2');

        // Moyenne générale annuelle
        $moyGen = null;
        if ($moyS1 !== null && $moyS2 !== null) {
            $moyGen = round(($moyS1 + $moyS2) / 2, 2);
        } elseif ($moyS1 !== null) {
            $moyGen = $moyS1;
        } elseif ($moyS2 !== null) {
            $moyGen = $moyS2;
        }

        // Déterminer le résultat
        $resultat = 'en_cours';
        $nextLevel = null;
        if ($moyGen !== null) {
            if ($moyGen >= 10) {
                $resultat = 'admis';
                $nextLevel = $levelMap[$normalizedLevel] ?? null;
            } else {
                $resultat = 'redoublant';
                $nextLevel = $normalizedLevel; // Reste au même niveau
            }
        }

        $preview[] = [
            'id'             => $sid,
            'student_code'   => $s['student_code'],
            'full_name'      => $s['first_name'] . ' ' . $s['last_name'],
            'current_level'  => $normalizedLevel,
            'current_class'  => $s['class_name'],
            'moyenne_s1'     => $moyS1,
            'moyenne_s2'     => $moyS2,
            'moyenne_gen'    => $moyGen,
            'resultat'       => $resultat,
            'next_level'     => $nextLevel,
            'mention'        => $moyGen !== null ? calculate_mention($moyGen) : '-',
        ];

        // Statistiques par niveau
        if (!isset($statsPerLevel[$normalizedLevel])) {
            $statsPerLevel[$normalizedLevel] = ['total' => 0, 'admis' => 0, 'redoublants' => 0, 'sans_notes' => 0];
        }
        $statsPerLevel[$normalizedLevel]['total']++;
        if ($resultat === 'admis') $statsPerLevel[$normalizedLevel]['admis']++;
        elseif ($resultat === 'redoublant') $statsPerLevel[$normalizedLevel]['redoublants']++;
        else $statsPerLevel[$normalizedLevel]['sans_notes']++;
    }

    json_response(true, 'Aperçu du passage annuel', [
        'annee_courante' => $anneeCourante,
        'total_eleves'   => count($students),
        'preview'        => $preview,
        'stats_par_niveau' => $statsPerLevel,
    ]);
}

// ============================================================
// ACTION: Lancer le passage annuel (execute)
// ============================================================
if ($action === 'execute') {
    $pdo = db_connection();

    // Déterminer les années scolaires
    $y = (int) date('Y');
    $m = (int) date('m');
    if ($m >= 9) {
        $anneeCourante = $y . '-' . ($y + 1);
        $anneeProchaine = ($y + 1) . '-' . ($y + 2);
    } else {
        $anneeCourante = ($y - 1) . '-' . $y;
        $anneeProchaine = $y . '-' . ($y + 1);
    }

    // Vérifier si le passage a déjà été fait pour cette année
    $existing = db_query(
        'SELECT id FROM promotions WHERE annee_scolaire_from = :af AND annee_scolaire_to = :at LIMIT 1',
        ['af' => $anneeCourante, 'at' => $anneeProchaine]
    )->fetch();

    if ($existing) {
        json_response(false, 'تم إجراء الترحيل لهذه السنة الدراسية من قبل (' . $anneeCourante . ' → ' . $anneeProchaine . ').');
    }

    try {
        $pdo->beginTransaction();

        $levelMap = get_level_map();

        // Récupérer tous les étudiants actifs
        $students = db_query(
            "SELECT id, student_code, first_name, last_name, class_name, level
             FROM students
             WHERE registration_status IN ('registered', 'active')
             ORDER BY level, class_name, last_name"
        )->fetchAll();

        $totalAdmis = 0;
        $totalRedoublants = 0;
        $totalDiplomes = 0;
        $studentsNextLevel = []; // level => [student_ids]
        $detailsEleves = [];

        foreach ($students as $s) {
            $sid = (int) $s['id'];
            $normalizedLevel = normalize_level($s['class_name'], $s['level']);

            $moyS1 = calculate_semester_average($sid, 'S1');
            $moyS2 = calculate_semester_average($sid, 'S2');

            $moyGen = null;
            if ($moyS1 !== null && $moyS2 !== null) {
                $moyGen = round(($moyS1 + $moyS2) / 2, 2);
            } elseif ($moyS1 !== null) {
                $moyGen = $moyS1;
            } elseif ($moyS2 !== null) {
                $moyGen = $moyS2;
            }

            // Si pas de notes, on considère "en_cours" et on ne change rien
            if ($moyGen === null) {
                // Sauvegarder dans l'historique quand même
                db_query(
                    'INSERT INTO historique_scolaire
                        (student_id, annee_scolaire, level, class_name, moyenne_s1, moyenne_s2, moyenne_generale, resultat, mention)
                     VALUES (:sid, :annee, :level, :class, :ms1, :ms2, :mg, :res, :mention)
                     ON DUPLICATE KEY UPDATE
                        moyenne_s1 = VALUES(moyenne_s1),
                        moyenne_s2 = VALUES(moyenne_s2),
                        moyenne_generale = VALUES(moyenne_generale),
                        resultat = VALUES(resultat)',
                    [
                        'sid'     => $sid,
                        'annee'   => $anneeCourante,
                        'level'   => $normalizedLevel,
                        'class'   => $s['class_name'],
                        'ms1'     => $moyS1,
                        'ms2'     => $moyS2,
                        'mg'      => $moyGen,
                        'res'     => 'en_cours',
                        'mention' => null,
                    ]
                );
                continue;
            }

            $mention = calculate_mention($moyGen);

            if ($moyGen >= 10) {
                // ADMIS
                $resultat = 'admis';
                $nextLevel = $levelMap[$normalizedLevel] ?? null;
                $totalAdmis++;

                if ($nextLevel === null) {
                    // Diplômé - fin de cycle (2BAC)
                    $totalDiplomes++;
                    // Mettre à jour le statut du student
                    db_query(
                        "UPDATE students SET registration_status = 'graduated' WHERE id = :id",
                        ['id' => $sid]
                    );
                } else {
                    // Passer au niveau suivant
                    db_query(
                        'UPDATE students SET level = :newLevel WHERE id = :id',
                        ['newLevel' => $nextLevel, 'id' => $sid]
                    );

                    // Regrouper pour la distribution par classes
                    if (!isset($studentsNextLevel[$nextLevel])) {
                        $studentsNextLevel[$nextLevel] = [];
                    }
                    $studentsNextLevel[$nextLevel][] = $sid;
                }
            } else {
                // REDOUBLANT
                $resultat = 'redoublant';
                $totalRedoublants++;

                // Reste au même niveau
                if (!isset($studentsNextLevel[$normalizedLevel])) {
                    $studentsNextLevel[$normalizedLevel] = [];
                }
                $studentsNextLevel[$normalizedLevel][] = $sid;
            }

            // Sauvegarder dans l'historique scolaire
            db_query(
                'INSERT INTO historique_scolaire
                    (student_id, annee_scolaire, level, class_name, moyenne_s1, moyenne_s2, moyenne_generale, resultat, mention)
                 VALUES (:sid, :annee, :level, :class, :ms1, :ms2, :mg, :res, :mention)
                 ON DUPLICATE KEY UPDATE
                    moyenne_s1 = VALUES(moyenne_s1),
                    moyenne_s2 = VALUES(moyenne_s2),
                    moyenne_generale = VALUES(moyenne_generale),
                    resultat = VALUES(resultat),
                    mention = VALUES(mention)',
                [
                    'sid'     => $sid,
                    'annee'   => $anneeCourante,
                    'level'   => $normalizedLevel,
                    'class'   => $s['class_name'],
                    'ms1'     => $moyS1,
                    'ms2'     => $moyS2,
                    'mg'      => $moyGen,
                    'res'     => $resultat,
                    'mention' => $mention,
                ]
            );

            $detailsEleves[] = [
                'student_code' => $s['student_code'],
                'full_name'    => $s['first_name'] . ' ' . $s['last_name'],
                'level_from'   => $normalizedLevel,
                'moyenne'      => $moyGen,
                'resultat'     => $resultat,
                'mention'      => $mention,
            ];
        }

        // ============================================================
        // Création automatique des classes (max 30 élèves/classe)
        // ============================================================
        $totalClassesCreees = 0;
        $repartition = [];

        foreach ($studentsNextLevel as $level => $studentIds) {
            $nbEleves = count($studentIds);
            $nbClasses = (int) ceil($nbEleves / 30);

            $classesForLevel = [];

            for ($i = 0; $i < $nbClasses; $i++) {
                $nomClasse = generate_class_name($level, $i);

                // Créer la classe
                db_query(
                    'INSERT INTO classes (nom_classe, level, annee_scolaire, capacite_max, effectif_actuel)
                     VALUES (:nom, :level, :annee, 30, 0)
                     ON DUPLICATE KEY UPDATE effectif_actuel = 0',
                    [
                        'nom'   => $nomClasse,
                        'level' => $level,
                        'annee' => $anneeProchaine,
                    ]
                );

                $classeId = (int) $pdo->lastInsertId();
                // Si ON DUPLICATE KEY, lastInsertId peut être 0
                if ($classeId <= 0) {
                    $row = db_query(
                        'SELECT id FROM classes WHERE nom_classe = :nom AND annee_scolaire = :annee LIMIT 1',
                        ['nom' => $nomClasse, 'annee' => $anneeProchaine]
                    )->fetch();
                    $classeId = (int) ($row['id'] ?? 0);
                }

                $classesForLevel[] = [
                    'id'   => $classeId,
                    'nom'  => $nomClasse,
                    'eleves' => [],
                ];
                $totalClassesCreees++;
            }

            // Distribuer les élèves dans les classes
            shuffle($studentIds); // Mélanger pour une répartition équitable
            foreach ($studentIds as $idx => $sid) {
                $classIndex = $idx % $nbClasses;
                $classeId = $classesForLevel[$classIndex]['id'];
                $nomClasse = $classesForLevel[$classIndex]['nom'];

                // Affecter l'élève à la classe
                db_query(
                    'INSERT INTO affectations_classes (student_id, classe_id, annee_scolaire)
                     VALUES (:sid, :cid, :annee)
                     ON DUPLICATE KEY UPDATE classe_id = VALUES(classe_id)',
                    [
                        'sid'   => $sid,
                        'cid'   => $classeId,
                        'annee' => $anneeProchaine,
                    ]
                );

                // Mettre à jour class_name dans students
                db_query(
                    'UPDATE students SET class_name = :cn WHERE id = :id',
                    ['cn' => $nomClasse, 'id' => $sid]
                );

                $classesForLevel[$classIndex]['eleves'][] = $sid;
            }

            // Mettre à jour l'effectif de chaque classe
            foreach ($classesForLevel as $cf) {
                $effectif = count($cf['eleves']);
                db_query(
                    'UPDATE classes SET effectif_actuel = :eff WHERE id = :id',
                    ['eff' => $effectif, 'id' => $cf['id']]
                );
            }

            $repartition[$level] = array_map(function ($cf) {
                return [
                    'nom_classe' => $cf['nom'],
                    'effectif'   => count($cf['eleves']),
                ];
            }, $classesForLevel);
        }

        // ============================================================
        // Enregistrer la promotion
        // ============================================================
        $promotedBy = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] :
                      (isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null);

        db_query(
            'INSERT INTO promotions
                (annee_scolaire_from, annee_scolaire_to, total_eleves, total_admis, total_redoublants, total_classes_creees, promoted_by)
             VALUES (:af, :at, :te, :ta, :tr, :tc, :pb)',
            [
                'af' => $anneeCourante,
                'at' => $anneeProchaine,
                'te' => count($students),
                'ta' => $totalAdmis,
                'tr' => $totalRedoublants,
                'tc' => $totalClassesCreees,
                'pb' => $promotedBy,
            ]
        );

        $promotionId = (int) $pdo->lastInsertId();

        // Mettre à jour promotion_id dans historique_scolaire
        db_query(
            'UPDATE historique_scolaire SET promotion_id = :pid WHERE annee_scolaire = :annee AND promotion_id IS NULL',
            ['pid' => $promotionId, 'annee' => $anneeCourante]
        );

        $pdo->commit();

        json_response(true, 'تم الترحيل السنوي بنجاح ✅', [
            'promotion_id'      => $promotionId,
            'annee_from'        => $anneeCourante,
            'annee_to'          => $anneeProchaine,
            'total_eleves'      => count($students),
            'total_admis'       => $totalAdmis,
            'total_redoublants' => $totalRedoublants,
            'total_diplomes'    => $totalDiplomes,
            'total_classes'     => $totalClassesCreees,
            'repartition'       => $repartition,
            'details_eleves'    => $detailsEleves,
        ]);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Promotion annuelle error: ' . $e->getMessage());
        json_response(false, 'خطأ أثناء الترحيل: ' . $e->getMessage(), [], 500);
    }
}

// ============================================================
// ACTION: Historique des promotions
// ============================================================
if ($action === 'history') {
    try {
        $promotions = db_query(
            'SELECT * FROM promotions ORDER BY date_promotion DESC LIMIT 20'
        )->fetchAll();

        json_response(true, 'سجل الترحيلات', ['promotions' => $promotions]);
    } catch (Throwable $e) {
        json_response(false, 'خطأ في جلب السجل: ' . $e->getMessage(), [], 500);
    }
}

// ============================================================
// ACTION: Historique scolaire d'un élève
// ============================================================
if ($action === 'student_history') {
    $studentId = (int) ($_POST['student_id'] ?? 0);
    if ($studentId <= 0) {
        json_response(false, 'معرف الطالب غير صالح.');
    }

    try {
        $history = db_query(
            'SELECT * FROM historique_scolaire WHERE student_id = :sid ORDER BY annee_scolaire DESC',
            ['sid' => $studentId]
        )->fetchAll();

        json_response(true, 'السجل الدراسي', ['history' => $history]);
    } catch (Throwable $e) {
        json_response(false, 'خطأ: ' . $e->getMessage(), [], 500);
    }
}

json_response(false, 'الإجراء المطلوب غير مدعوم.', [], 400);
