<?php
function init_buttons_display() {
    $script = "
        jQuery(document).ready(function($) {
            console.log('Initialisation des boutons');
            function addButtons() {
                const formContainer = document.querySelector('#mwai-form-container-h34xjdfh0');
                console.log('Form container:', formContainer);
                
                if (formContainer && !document.querySelector('.kwiik-action-buttons')) {
                    const buttonContainer = document.createElement('div');
                    buttonContainer.className = 'kwiik-action-buttons';
                    buttonContainer.style.cssText = 'display: flex; gap: 10px; margin-top: 10px;';

                    const pdfButton = document.createElement('button');
                    pdfButton.innerHTML = '<span>Télécharger en PDF</span>';
                    pdfButton.className = 'mwai-copy-button';
                    pdfButton.style.flex = '1';
                    pdfButton.onclick = function() {
                        console.log('PDF button clicked');
                        if (typeof downloadPDF === 'function') {
                            downloadPDF();
                        } else {
                            console.error('downloadPDF function not found');
                        }
                    };

                    const emailButton = document.createElement('button');
                    emailButton.innerHTML = '<span>Envoyer par email</span>';
                    emailButton.className = 'mwai-copy-button';
                    emailButton.style.flex = '1';
                    emailButton.onclick = function() {
                        console.log('Email button clicked');
                        if (typeof showEmailForm === 'function') {
                            showEmailForm();
                        } else {
                            console.error('showEmailForm function not found');
                        }
                    };

                    buttonContainer.appendChild(pdfButton);
                    buttonContainer.appendChild(emailButton);
                    formContainer.appendChild(buttonContainer);
                    console.log('Boutons ajoutés');
                }
            }

            let attempts = 0;
            const interval = setInterval(function() {
                console.log('Tentative d\'ajout des boutons:', attempts + 1);
                addButtons();
                attempts++;
                if (attempts >= 10) clearInterval(interval);
            }, 500);
        });
    ";
    
    wp_add_inline_script('jquery', $script, 'after');

    // Styles pour tous les boutons
    $styles = "
        /* Style pour le bouton Construire votre programme */
        .mwai-form-submit button {
            background: #1B8C8E !important;
            background-image: none !important;
            border: none !important;
        }

        .mwai-form-submit button:hover {
            background: #157577 !important;
            opacity: 0.9 !important;
        }

        /* Style pour les autres boutons */
        .kwiik-action-buttons {
            display: flex !important;
            gap: 10px !important;
            margin-top: 10px !important;
            width: 100% !important;
        }

        .kwiik-action-buttons .mwai-copy-button {
            flex: 1;
            background: #1B8C8E !important;
            background-image: none !important;
        }

        .mwai-copy-button:hover {
            background: #157577 !important;
            opacity: 0.9 !important;
        }
    ";
    
    wp_add_inline_style('wp-block-library', $styles);
}
add_action('wp_enqueue_scripts', 'init_buttons_display');