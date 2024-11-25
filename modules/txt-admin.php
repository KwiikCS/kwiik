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

// Fonction pour obtenir les destinations les plus fréquentes
function get_most_common_destinations($files) {
    $destinations = [];
    foreach ($files as $file) {
        $filename = basename($file);
        // Modification du regex pour extraire correctement la destination
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
    foreach ($files as $file) {
        $filename = basename($file);
        preg_match('/\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_([^_]+)_/', $filename, $matches);
        if (isset($matches[1])) {
            $dest = urldecode($matches[1]);
            $destinations[] = $dest;
        }
    }

    $stats = [
        'total_files' => count($files),
        'files_today' => count(array_filter($files, function($file) {
            return date('Y-m-d', filemtime($file)) === date('Y-m-d');
        })),
        'total_destinations' => count(array_unique($destinations)),
        'most_common_destinations' => get_most_common_destinations($files)
    ];

    ?>
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
    <?php
}

// Styles CSS
function add_txt_admin_styles() {
    ?>
    <style>
        .stats-grid {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 15px;
            text-align: center;
            flex: 1;
        }
        /* Styles modifiés pour la fenêtre modale */
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
    </style>
    <?php
}
add_action('admin_head', 'add_txt_admin_styles');

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

    // Pagination
    $per_page = 20;
    $total_files = count($filtered_files);
    $total_pages = ceil($total_files / $per_page);
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    $filtered_files = array_slice($filtered_files, $offset, $per_page);

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
                    <th>Date de création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filtered_files)): ?>
                    <tr>
                        <td colspan="4">Aucun programme trouvé</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filtered_files as $file): 
                        $filename = basename($file);
                        $filedate = date('Y-m-d H:i:s', filemtime($file));
                        
                        // Extraire la destination du nom de fichier
                        preg_match('/(\d{4}-\d{2}-\d{2})_([^_]+)_/', $filename, $matches);
                        $destination = $matches[2] ?? 'N/A';
                    ?>
                        <tr>
                            <td><?php echo esc_html($filename); ?></td>
                            <td><?php echo esc_html(urldecode($destination)); ?></td>
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