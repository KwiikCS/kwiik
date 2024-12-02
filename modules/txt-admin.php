<?php
/**
 * Interface d'administration pour les programmes des voyageurs
 */

// Ajouter un menu dans l'administration WordPress
function add_voyageurs_menu() {
    add_menu_page(
        'Les Programmes des Voyageurs',  // Titre de la page
        'Nos Voyageurs',                 // Titre du menu
        'manage_options',                // Capacité requise
        'programme-txt-files',           // Slug du menu
        'display_voyageurs_page',        // Fonction de callback
        'dashicons-admin-site-alt3',     // Icône de monde/map
        30                               // Position dans le menu
    );
}
add_action('admin_menu', 'add_voyageurs_menu');

// Styles CSS et Scripts
function add_txt_admin_styles() {
    // Enregistrer et charger Chart.js
    wp_register_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array('jquery'), '3.7.0', true);
    wp_enqueue_script('chartjs');

    ?>
    <style>
        .voyageurs-statistics-container {
            display: flex;
            gap: 30px;
            margin: 20px 0;
        }
        .voyageurs-statistics,
        .voyageurs-chart {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 48%;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 15px;
            text-align: center;
            border-radius: 6px;
        }
        .stat-box h3 {
            color: #1B8C8E;
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .stat-box p {
            font-size: 24px;
            margin: 0;
            color: #333;
        }
        .busiest-days {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .days-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        .day-stat {
            background: white;
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid #1B8C8E;
        }
        .day-stat .day {
            display: block;
            color: #1B8C8E;
            font-weight: bold;
        }
        .day-stat .count {
            display: block;
            font-size: 0.9em;
            color: #666;
        }
        #txt-file-modal .media-modal {
            width: 90%;
            left: 5%;
            right: 5%;
        }
        #txt-file-modal .media-modal-content {
            width: 100%;
            max-width: none;
            height: 90vh;
            margin: 0 auto;
        }
        #txt-file-modal .media-frame-content {
            top: 50px;
            bottom: 60px;
            padding: 20px;
            overflow: auto;
        }
        #txt-file-modal pre {
            max-height: none;
            overflow-y: visible;
            white-space: pre-wrap;
            word-wrap: break-word;
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        #txt-file-modal .media-frame-toolbar {
            bottom: 0;
            position: absolute;
            width: 100%;
        }
        #txt-file-modal .form-table {
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .voyageurs-statistics-container {
                flex-direction: column;
            }
            .voyageurs-statistics,
            .voyageurs-chart {
                width: 100%;
            }
            .stats-grid,
            .days-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php
}
add_action('admin_enqueue_scripts', 'add_txt_admin_styles');

// Fonction pour obtenir les destinations les plus fréquentes
function get_most_common_destinations($files) {
    $destinations = [];
    foreach ($files as $file) {
        $filename = basename($file);
        preg_match('/\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_([^_]+)_/', $filename, $matches);
        if (isset($matches[1])) {
            $dest = urldecode($matches[1]);
            $destinations[$dest] = ($destinations[$dest] ?? 0) + 1;
        }
    }
    arsort($destinations);
    return array_slice($destinations, 0, 5);
}

function add_voyageurs_statistics_section($files) {
    $destinations = [];
    $days_activity = [];
    
    foreach ($files as $file) {
        $filename = basename($file);
        preg_match('/\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_([^_]+)_/', $filename, $matches);
        if (isset($matches[1])) {
            $dest = urldecode($matches[1]);
            $destinations[] = $dest;
        }
        
        // Compter l'activité par jour
        $day = date('l', filemtime($file));
        $days_activity[$day] = ($days_activity[$day] ?? 0) + 1;
    }
    arsort($days_activity);

    $stats = [
        'total_files' => count($files),
        'files_today' => count(array_filter($files, function($file) {
            return date('Y-m-d', filemtime($file)) === date('Y-m-d');
        })),
        'total_destinations' => count(array_unique($destinations)),
        'most_common_destinations' => get_most_common_destinations($files),
        'this_week' => count(array_filter($files, function($file) {
            return strtotime(date('Y-m-d', filemtime($file))) >= strtotime('-7 days');
        })),
        'this_month' => count(array_filter($files, function($file) {
            return date('Y-m', filemtime($file)) === date('Y-m');
        })),
        'average_per_day' => round(count($files) / max(1, count(array_unique(array_map(function($file) {
            return date('Y-m-d', filemtime($file));
        }, $files)))), 1),
        'busiest_days' => array_slice($days_activity, 0, 3)
    ];

    ?>
    <div class="voyageurs-statistics-container">
        <!-- Colonne de gauche -->
        <div class="voyageurs-statistics">
            <h2>Statistiques des Voyageurs</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <h3>Nombre total de programmes</h3>
                    <p><?php echo $stats['total_files']; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Programmes aujourd'hui</h3>
                    <p><?php echo $stats['files_today']; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Nombre de destinations</h3>
                    <p><?php echo $stats['total_destinations']; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Cette semaine</h3>
                    <p><?php echo $stats['this_week']; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Ce mois</h3>
                    <p><?php echo $stats['this_month']; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Moyenne par jour</h3>
                    <p><?php echo $stats['average_per_day']; ?></p>
                </div>
            </div>

            <div class="busiest-days">
                <h3>Jours les plus actifs</h3>
                <div class="days-grid">
                    <?php 
                    $days_fr = [
                        'Monday' => 'Lundi',
                        'Tuesday' => 'Mardi',
                        'Wednesday' => 'Mercredi',
                        'Thursday' => 'Jeudi',
                        'Friday' => 'Vendredi',
                        'Saturday' => 'Samedi',
                        'Sunday' => 'Dimanche'
                    ];
                    foreach($stats['busiest_days'] as $day => $count): ?>
                        <div class="day-stat">
                            <span class="day"><?php echo $days_fr[$day]; ?></span>
                            <span class="count"><?php echo $count; ?> programmes</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <h3>Destinations les plus fréquentes</h3>
            <table class="wp-list-table widefat">
                <?php foreach($stats['most_common_destinations'] as $dest => $count): ?>
                    <tr>
                        <td><?php echo esc_html($dest); ?></td>
                        <td><?php echo $count; ?> programmes</td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <!-- Colonne de droite -->
        <div class="voyageurs-chart">
            <h2>Répartition des destinations</h2>
            <div style="position: relative; height: 300px;">
                <canvas id="destinationsChart"></canvas>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js not loaded');
            return;
        }

        const ctx = document.getElementById('destinationsChart').getContext('2d');
        const destinations = <?php echo json_encode(array_keys($stats['most_common_destinations'])); ?>;
        const counts = <?php echo json_encode(array_values($stats['most_common_destinations'])); ?>;
        
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: destinations,
                datasets: [{
                    data: counts,
                    backgroundColor: [
                        '#1B8C8E',
                        '#26A5A8',
                        '#31BDBF',
                        '#3CD4D7',
                        '#47EBEE'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                family: 'Quicksand'
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
    <?php
}

// Fonction principale d'affichage
function display_voyageurs_page() {
    // Vérifier les permissions
    if (!current_user_can('manage_options')) {
        wp_die('Vous n\'avez pas les permissions nécessaires.');
    }

    // Récupérer les fichiers TXT
    $upload_dir = wp_upload_dir();
    $txt_folder = $upload_dir['basedir'] . '/programme_txt';
    $files = glob($txt_folder . '/*.txt');

    // Filtres
    $current_filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';
    $search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

    // Ordre de tri
    $current_order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';

    // Filtrer et rechercher les fichiers
    $filtered_files = array_filter($files, function($file) use ($current_filter, $search_term) {
        $filename = basename($file);
        $file_date = date('Y-m-d', filemtime($file));
        
        // Filtres par date
        if ($current_filter === 'today' && $file_date !== date('Y-m-d')) return false;
        if ($current_filter === 'this_week' && strtotime($file_date) < strtotime('-7 days')) return false;

        // Recherche
        if (!empty($search_term)) {
            $content = file_get_contents($file);
            if (stripos($content, $search_term) === false && 
                stripos($filename, $search_term) === false) {
                return false;
            }
        }

        return true;
    });

    // Trier les fichiers par date de création
    usort($filtered_files, function($a, $b) use ($current_order) {
        $a_time = filemtime($a);
        $b_time = filemtime($b);
        return $current_order === 'asc' ? $a_time - $b_time : $b_time - $a_time;
    });

    // Pagination
    $per_page = 20;
    $total_files = count($filtered_files);
    $total_pages = ceil($total_files / $per_page);
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    $paginated_files = array_slice($filtered_files, $offset, $per_page);

    ?>
    
    <div class="wrap">
        <h1>Les Programmes des Voyageurs</h1>
        
        <?php add_voyageurs_statistics_section($files); ?>

        <form method="get" action="">
            <input type="hidden" name="page" value="programme-txt-files">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="filter">
                        <option value="all" <?php selected($current_filter, 'all'); ?>>Tous les programmes</option>
                        <option value="today" <?php selected($current_filter, 'today'); ?>>Programmes du jour</option>
                        <option value="this_week" <?php selected($current_filter, 'this_week'); ?>>Programmes de la semaine</option>
                    </select>
                    <input type="text" name="search" placeholder="Rechercher..." 
                           value="<?php echo esc_attr($search_term); ?>">
                    <input type="submit" class="button" value="Filtrer">
                </div>
            </div>
        </form>

        <table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Nom du fichier</th>
            <th>Destination</th>
            <th>
                <a href="<?php echo add_query_arg(array('order' => $current_order === 'asc' ? 'desc' : 'asc')); ?>">
                    Date de création
                    <?php if ($current_order === 'asc'): ?>
                        <span>▲</span>
                    <?php else: ?>
                        <span>▼</span>
                    <?php endif; ?>
                </a>
            </th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($paginated_files)): ?>
            <tr>
                <td colspan="4">Aucun programme trouvé</td>
            </tr>
        <?php else: ?>
            <?php foreach ($paginated_files as $file): 
                $filename = basename($file);
                $filedate = date('Y-m-d H:i:s', filemtime($file));

                preg_match('/\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_([^_]+)_/', $filename, $matches);
                $destination = isset($matches[1]) ? urldecode($matches[1]) : 'N/A';
            ?>
                <tr>
                    <td><?php echo esc_html($filename); ?></td>
                    <td><?php echo esc_html($destination); ?></td>
                    <td><?php echo esc_html($filedate); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=programme-txt-files&action=view&file=' . urlencode($filename)); ?>" class="button view-txt-file">Voir</a>
                        <a href="<?php echo admin_url('admin.php?page=programme-txt-files&action=download&file=' . urlencode($filename)); ?>" class="button">Télécharger</a>
                        <a href="<?php echo admin_url('admin.php?page=programme-txt-files&action=delete&file=' . urlencode($filename)); ?>" class="button delete-txt-file" onclick="return confirm('Voulez-vous vraiment supprimer ce programme ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div class="tablenav bottom">
    <div class="tablenav-pages">
        <?php
        $base_url = add_query_arg(array(
            'page' => 'programme-txt-files',
            'filter' => $current_filter,
            'search' => $search_term,
            'order' => $current_order
        ));
        echo paginate_links(array(
            'base' => $base_url . '%_%',
            'format' => '&paged=%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;'
        ));
        ?>
    </div>
</div>

</div>

<script>
jQuery(document).ready(function($) {
    $('.view-txt-file').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        
        $.get(url, function(response) {
            $('body').append(response);
        });
    });

    $(document).on('click', '.media-modal-close, .media-modal-backdrop', function(e) {
        e.preventDefault();
        $('#txt-file-modal').remove();
    });
});
</script>
<?php
}


// Fonction pour visualiser un fichier TXT
function view_txt_file($file_path) {
    if (!file_exists($file_path)) {
        wp_die('Fichier non trouvé');
    }

    $content = file_get_contents($file_path);
    $filename = basename($file_path);

    // Récupérer les informations du fichier
    preg_match('/(\d{4}-\d{2}-\d{2})_([^_]+)_([^.]+)\.txt/', $filename, $matches);
    $date = $matches[1] ?? 'Date inconnue';
    $destination = urldecode($matches[2] ?? 'Destination inconnue');
    $user = urldecode($matches[3] ?? 'Utilisateur inconnu');

    ?>
    <div id="txt-file-modal" style="display:none;">
        <div class="media-modal wp-core-ui">
            <a class="media-modal-close" href="#" title="Fermer">
                <span class="media-modal-icon"></span>
            </a>
            <div class="media-modal-content">
                <div class="media-frame-title">
                    <h1>Détails du programme</h1>
                </div>
                <div class="media-frame-content">
                    <div class="media-frame-toolbar">
                        <div class="media-toolbar">
                            <div class="media-toolbar-primary">
                                <table class="form-table">
                                    <tr>
                                        <th>Nom du fichier</th>
                                        <td><?php echo esc_html($filename); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date de création</th>
                                        <td><?php echo esc_html($date); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Destination</th>
                                        <td><?php echo esc_html($destination); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Créé par</th>
                                        <td><?php echo esc_html($user); ?></td>
                                    </tr>
                                </table>

                                <h2>Contenu du programme</h2>
                                <pre><?php echo esc_html($content); ?></pre>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="media-frame-toolbar">
                    <div class="media-toolbar">
                        <div class="media-toolbar-primary">
                            <a href="<?php echo admin_url('admin.php?page=programme-txt-files&action=download&file=' . urlencode($filename)); ?>" class="button">Télécharger</a>
                            <a href="#" class="button button-primary media-modal-close">Fermer</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="media-modal-backdrop"></div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#txt-file-modal').show();
    });
    </script>
    <?php
    exit;
}

// Fonction pour télécharger un fichier TXT
function download_txt_file($file_path) {
    if (!file_exists($file_path)) {
        wp_die('Fichier non trouvé');
    }

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    readfile($file_path);
    exit;
}

// Fonction pour supprimer un fichier TXT
function delete_txt_file($file_path) {
    if (!current_user_can('manage_options')) {
        wp_die('Vous n\'avez pas les permissions nécessaires.');
    }

    if (file_exists($file_path)) {
        unlink($file_path);
        wp_redirect(admin_url('admin.php?page=programme-txt-files&deleted=true'));
        exit;
    } else {
        wp_die('Fichier non trouvé');
    }
}

// Gestionnaire des actions
add_action('admin_init', 'handle_txt_file_actions');
function handle_txt_file_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'programme-txt-files') {
        return;
    }

    $upload_dir = wp_upload_dir();
    $txt_folder = $upload_dir['basedir'] . '/programme_txt';

    if (isset($_GET['action']) && isset($_GET['file'])) {
        $file = sanitize_file_name($_GET['file']);
        $file_path = $txt_folder . '/' . $file;

        switch($_GET['action']) {
            case 'view':
                view_txt_file($file_path);
                break;
            case 'download':
                download_txt_file($file_path);
                break;
            case 'delete':
                delete_txt_file($file_path);
                break;
        }
    }
}
