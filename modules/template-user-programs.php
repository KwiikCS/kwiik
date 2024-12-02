<?php
/*
Template Name: Programmes de l'utilisateur
*/

get_header();
?>

<div class="user-programs-container">
    <h1>Mes programmes</h1>

    <?php
    $current_user = wp_get_current_user();
    $current_user_login = $current_user->user_login;

    if ($current_user_login) {
        $upload_dir = wp_upload_dir();
        $txt_folder = $upload_dir['basedir'] . '/programme_txt';

        $files = scandir($txt_folder);

        $user_files = array();

        $pattern = '/^.*_' . preg_quote($current_user_login, '/') . '\.txt$/';

        foreach ($files as $file) {
            if (preg_match($pattern, $file)) {
                $user_files[] = $file;
            }
        }

        if (!empty($user_files)) {
            // Tri des programmes
            $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date';
            $sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'desc';

            usort($user_files, function($a, $b) use ($sort_by, $sort_order) {
                $a_parts = explode('_', $a);
                $b_parts = explode('_', $b);

                if ($sort_by === 'date') {
                    $a_date = $a_parts[0];
                    $b_date = $b_parts[0];
                    return $sort_order === 'asc' ? strcmp($a_date, $b_date) : strcmp($b_date, $a_date);
                } elseif ($sort_by === 'destination') {
                    $a_destination = $a_parts[2];
                    $b_destination = $b_parts[2];
                    return $sort_order === 'asc' ? strcmp($a_destination, $b_destination) : strcmp($b_destination, $a_destination);
                }
            });

            // Recherche
            $search_query = isset($_GET['search']) ? $_GET['search'] : '';
            if (!empty($search_query)) {
                $user_files = array_filter($user_files, function($file) use ($search_query) {
                    return stripos($file, $search_query) !== false;
                });
            }

            // Pagination
            $items_per_page = 5;
            $total_items = count($user_files);
            $total_pages = ceil($total_items / $items_per_page);
            $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $offset = ($current_page - 1) * $items_per_page;

            // Formulaire de tri et de recherche
            ?>
            <div class="filters-container">
                <form method="get" class="sort-search-form">
                    <input type="hidden" name="page" value="<?php echo get_query_var('pagename'); ?>">
                    <select name="sort_by">
                        <option value="date" <?php selected($sort_by, 'date'); ?>>Trier par date</option>
                        <option value="destination" <?php selected($sort_by, 'destination'); ?>>Trier par destination</option>
                    </select>
                    <select name="sort_order">
                        <option value="desc" <?php selected($sort_order, 'desc'); ?>>Plus récent</option>
                        <option value="asc" <?php selected($sort_order, 'asc'); ?>>Plus ancien</option>
                    </select>
                    <input type="text" name="search" placeholder="Rechercher..." value="<?php echo esc_attr($search_query); ?>">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                </form>
            </div>

            <?php
            // Affichage des programmes
            echo '<div class="user-programs-grid">';
            foreach (array_slice($user_files, $offset, $items_per_page) as $file) {
                $destination = explode('_', $file)[2];
                $date = explode('_', $file)[0];
                echo '<div class="program-card">';
                echo '<div class="card-header">';
                echo '<h2>' . esc_html(strtoupper($destination)) . '</h2>';
                echo '</div>';
                echo '<div class="card-body">';
                echo '<p>Date de création : ' . esc_html(date('d/m/Y', strtotime($date))) . '</p>';
                echo '<div class="card-actions">';
                echo '<button class="btn btn-primary view-program" data-file="' . esc_attr($file) . '">Voir</button>';
                echo '</button>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';

            // Pagination
            if ($total_pages > 1) {
                echo '<div class="pagination">';
                for ($i = 1; $i <= $total_pages; $i++) {
                    $current_url = add_query_arg([
                        'paged' => $i,
                        'sort_by' => $sort_by,
                        'sort_order' => $sort_order,
                        'search' => $search_query
                    ]);
                    echo '<a href="' . esc_url($current_url) . '" class="' . ($i === $current_page ? 'active' : '') . '">' . $i . '</a>';
                }
                echo '</div>';
            }
        } else {
            echo '<p>Vous n\'avez aucun programme pour le moment.</p>';
            echo '<a href="https://kwiik.travel/v2/" class="btn btn-primary">Créer votre 1er programme</a>';
        }
    } else {
        echo '<p>Veuillez vous connecter pour voir vos programmes.</p>';
    }
    ?>
</div>

<div id="program-content-container"></div>

<script>
jQuery(document).ready(function($) {
    $('.view-program').on('click', function() {
        const file = $(this).data('file');
        
        $.ajax({
            url: '<?php echo esc_url(content_url('uploads/programme_txt/' . $file)); ?>',
            method: 'GET',
            dataType: 'text',
            success: function(content) {
                $('#program-content-container').html('<div id="program-content"><pre>' + content + '</pre></div>');
                $('html, body').animate({
                    scrollTop: $('#program-content-container').offset().top
                }, 500);
            },
            error: function() {
                alert('Erreur lors du chargement du contenu du programme');
            }
        });
    });

    // Gestion des favoris
    $('.favorite-program').on('click', function() {
        const button = $(this);
        const file = button.data('file');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'handle_favorite_program',
                program_file: file
            },
            success: function(response) {
                if (response.success) {
                    button.find('i').toggleClass('favorite');
                }
            },
            error: function() {
                alert('Erreur lors de la mise à jour du favori');
            }
        });
    });
});
</script>

<style>
<style>
.user-programs-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.user-programs-container h1 {
    color: #1B8C8E;
    font-size: 36px;
    margin-bottom: 30px;
    text-align: center;
}

.user-programs-grid {
    display: flex;
    flex-direction: column;
    gap: 30px;
    align-items: center;
}

.program-card {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    width: 100%;
    max-width: 600px; /* Taille maximale sur desktop */
}

.card-header {
    background-color: #1B8C8E;
    padding: 20px;
    text-align: center;
}

.card-header h2 {
    color: #fff;
    font-size: 24px;
    margin: 0;
}

.card-body {
    padding: 20px;
}

.card-body p {
    color: #333;
    font-size: 18px;
    margin-bottom: 20px;
}

.card-actions {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 16px;
    cursor: pointer;
}

.btn-primary {
    background-color: #1B8C8E;
    color: #fff;
    border: none;
}

.btn-favorite {
    background: none;
    border: none;
    padding: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-favorite i {
    color: #ccc;
    font-size: 20px;
    transition: all 0.3s ease;
}

.btn-favorite.is-favorite i,
.btn-favorite i.favorite {
    color: #ff4d4d;
}

.btn-favorite:hover i {
    transform: scale(1.1);
}

#program-content-container {
    margin: 40px auto;
    max-width: 1200px;
    padding: 0 20px;
}

#program-content {
    white-space: pre-wrap;
    word-wrap: break-word;
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    font-size: 16px;
    line-height: 1.6;
}

/* Mobile */
@media (max-width: 768px) {
    .user-programs-container {
        padding: 20px 10px;
    }

    .user-programs-container h1 {
        font-size: 28px;
        margin-bottom: 20px;
    }

    .program-card {
        width: 300px; /* Taille fixe sur mobile */
        max-width: 300px;
    }

    #program-content-container {
        width: 90%;
        margin: 20px auto;
        padding: 0;
    }

    #program-content {
        padding: 15px;
        font-size: 14px;
        line-height: 1.5;
        width: 100%;
    }

    #program-content pre {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        white-space: pre-wrap;
        word-wrap: break-word;
        font-size: 14px;
        line-height: 1.6;
        color: #333;
    }
}

/* Styles supplémentaires pour les filtres et la pagination */

.filters-container {
    margin-bottom: 30px;
}

.sort-search-form {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.sort-search-form select,
.sort-search-form input[type="text"] {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 5px;
    min-width: 150px;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
}

.pagination a {
    padding: 8px 12px;
    border: 1px solid #1B8C8E;
    border-radius: 5px;
    color: #1B8C8E;
    text-decoration: none;
    transition: all 0.3s ease;
}

.pagination a.active,
.pagination a:hover {
    background-color: #1B8C8E;
    color: white;
}

@media (max-width: 768px) {
    .sort-search-form {
        flex-direction: column;
        align-items: stretch;
    }

    .sort-search-form select,
    .sort-search-form input[type="text"],
    .sort-search-form button {
        width: 100%;
    }
}
</style>

<?php
get_footer();
?>