<?php
/**
 * Intégration avec BuddyBoss
 */

function save_programme_to_buddyboss($file_path, $user_id) {
    // Vérifier si BuddyBoss est actif
    if (!function_exists('bp_document_add')) {
        error_log('BUDDYBOSS : Fonction bp_document_add non disponible');
        return false;
    }

    // Vérifier l'existence du fichier
    if (!file_exists($file_path)) {
        error_log('BUDDYBOSS : Fichier non trouvé : ' . $file_path);
        return false;
    }

    // Logs détaillés
    error_log('BUDDYBOSS : Tentative de sauvegarde');
    error_log('BUDDYBOSS : Fichier : ' . $file_path);
    error_log('BUDDYBOSS : User ID : ' . $user_id);
    error_log('BUDDYBOSS : Taille du fichier : ' . filesize($file_path) . ' octets');

    // Vérifier les permissions du fichier
    $file_permissions = substr(sprintf('%o', fileperms($file_path)), -4);
    error_log('BUDDYBOSS : Permissions du fichier : ' . $file_permissions);

    try {
        // Récupérer la destination du fichier
        preg_match('/(\d{4}-\d{2}-\d{2})_([^_]+)_/', basename($file_path), $matches);
        $destination = isset($matches[2]) ? urldecode($matches[2]) : 'Destination';

        $document_args = [
            'user_id' => $user_id,
            'title' => 'Programme - ' . $destination . ' - ' . date('d/m/Y'),
            'file' => $file_path,
            'privacy' => 'onlyme'
        ];

        // Log des arguments
        error_log('BUDDYBOSS : Arguments de sauvegarde : ' . print_r($document_args, true));

        // Vérifier les capacités de l'utilisateur
        if (!bp_loggedin_user_can('bp_docs_create')) {
            error_log('BUDDYBOSS : Utilisateur sans permission de créer des documents');
            return false;
        }

        $document_id = bp_document_add($document_args);

        if ($document_id) {
            error_log('BUDDYBOSS : Document sauvegardé avec l\'ID : ' . $document_id);
            return $document_id;
        } else {
            error_log('BUDDYBOSS : Échec de la sauvegarde du document');
            
            // Obtenir les dernières erreurs
            global $bp;
            if (!empty($bp->errors)) {
                error_log('BUDDYBOSS : Erreurs BuddyPress : ' . print_r($bp->errors, true));
            }

            return false;
        }
    } catch (Exception $e) {
        error_log('BUDDYBOSS : Erreur lors de la sauvegarde : ' . $e->getMessage());
        return false;
    }
}