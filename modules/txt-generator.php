<?php
/**
 * Générateur de fichiers TXT pour les programmes de voyage
 */

// Fonction pour générer le fichier TXT
function generate_programme_txt() {
    // Vérifier si les données POST sont présentes
    if (!isset($_POST['destination']) || !isset($_POST['content'])) {
        error_log('Données manquantes pour générer le TXT');
        return false;
    }

    $upload_dir = wp_upload_dir();
    $txt_folder = $upload_dir['basedir'] . '/programme_txt';
    
    // Créer le dossier s'il n'existe pas
    if (!file_exists($txt_folder)) {
        mkdir($txt_folder, 0755, true);
        file_put_contents($txt_folder . '/.htaccess', "Deny from all");
    }
    
    // Récupérer les informations de l'utilisateur connecté
    $current_user = wp_get_current_user();
    
    // Nettoyer et formater les données
    $destination = sanitize_text_field($_POST['destination']);
    $programme_text = sanitize_textarea_field($_POST['content']);
    
    // Générer un nom de fichier unique
    $filename = date('Y-m-d_H-i-s') . '_' . 
                sanitize_title($destination) . '_' . 
                sanitize_title($current_user->display_name) . 
                '.txt';
    
    // Préparer le contenu structuré
    $content = "=== Programme de voyage Kwiik Travel ===\n\n";
    $content .= "Date de création : " . date('d/m/Y H:i:s') . "\n";
    $content .= "Destination : " . $destination . "\n";
    $content .= "Créé par : " . $current_user->display_name . "\n";
    $content .= "Email : " . $current_user->user_email . "\n";
    $content .= "\n=== DESTINATION DÉTAILLÉE ===\n";
    $content .= $destination . "\n\n";
    $content .= "=== PROGRAMME DÉTAILLÉ ===\n\n";
    $content .= $programme_text . "\n\n";
    $content .= "=== FIN DU PROGRAMME ===\n";
    $content .= "Généré via Kwiik Travel - www.kwiik.travel\n";
    
    $file_path = $txt_folder . '/' . $filename;
    
    // Sauvegarder le fichier
    $result = file_put_contents($file_path, $content);
    
    // Log du résultat
    if ($result) {
        error_log('Fichier TXT généré : ' . $file_path);
        return $file_path;
    } else {
        error_log('Échec de la génération du fichier TXT');
        return false;
    }
}

// Hook pour générer le TXT
add_action('wp_ajax_generate_programme_txt', 'handle_generate_programme_txt');
add_action('wp_ajax_nopriv_generate_programme_txt', 'handle_generate_programme_txt');

function handle_generate_programme_txt() {
    $txt_file = generate_programme_txt();
    
    if ($txt_file) {
        wp_send_json_success([
            'message' => 'Fichier TXT généré',
            'file_path' => $txt_file
        ]);
    } else {
        wp_send_json_error('Erreur lors de la génération du fichier TXT');
    }
    wp_die();
}

// Fonction pour nettoyer les anciens fichiers TXT (optionnel)
function cleanup_old_txt_files() {
    $upload_dir = wp_upload_dir();
    $txt_folder = $upload_dir['basedir'] . '/programme_txt';
    
    // Supprimer les fichiers de plus de 30 jours
    $files = glob($txt_folder . '/*.txt');
    $now = time();
    
    foreach ($files as $file) {
        if ($now - filemtime($file) >= 30 * 24 * 60 * 60) {
            unlink($file);
        }
    }
}
add_action('wp_scheduled_delete', 'cleanup_old_txt_files');

// Planifier le nettoyage des fichiers
if (!wp_next_scheduled('wp_scheduled_delete')) {
    wp_schedule_event(time(), 'daily', 'wp_scheduled_delete');
}