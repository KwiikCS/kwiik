<?php
function init_email_sender() {
    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email ? $current_user->user_email : '';

    $email_script = "
        function showEmailForm() {
            const doc = generatePDF();
            if (!doc) {
                alert('Veuillez d\\'abord générer un programme');
                return;
            }

            const existingBox = document.querySelector('.email-share-box');
            if (existingBox) {
                existingBox.remove();
                return;
            }

            const box = document.createElement('div');
            box.className = 'email-share-box';
            box.style.cssText = `
                position: fixed;
                right: -400px;
                top: 50%;
                transform: translateY(-50%);
                width: 350px;
                background: white;
                padding: 20px;
                border-radius: 8px 0 0 8px;
                box-shadow: -2px 0 10px rgba(0,0,0,0.1);
                transition: right 0.3s ease;
                z-index: 1000;
            `;

            box.innerHTML = `
                <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;'>
                    <button onclick='closeEmailBox()' class='close-button'>×</button>
                </div>

                <!-- Section 1: Envoi à soi-même -->
                <div class='email-section'>
                    <h4 style='color: #1B8C8E; margin-bottom: 10px;'>M'envoyer le programme</h4>
                    <div style='margin-bottom: 20px;'>
                        <input type='email' value='" . esc_attr($user_email) . "' class='share-email-input' style='width: 100%; padding: 8px; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 4px;' readonly>
                        <button onclick='sendToSelf()' class='elementor-button' style='width: 100%;'>Me l'envoyer</button>
                    </div>
                </div>

                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>

                <!-- Section 2: Partage avec des amis -->
                <div class='email-section'>
                    <h4 style='color: #1B8C8E; margin-bottom: 10px;'>Partager avec mes amis</h4>
                    <p style='margin-bottom: 15px; font-size: 14px;'>Faites découvrir votre programme de voyage à vos proches !</p>
                    <div id='email-inputs' style='margin-bottom: 15px;'>
                        <input type='email' placeholder='Adresse email' class='share-email-input' style='width: 100%; padding: 8px; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 4px;'>
                    </div>
                    <button onclick='addEmailInput()' style='background: none; border: none; color: #1B8C8E; cursor: pointer; font-size: 14px; margin-bottom: 15px;'>+ Ajouter un email (max 5)</button>
                    <button onclick='sendEmailToPeople()' class='elementor-button' style='width: 100%;'>Envoyer</button>
                </div>
            `;

            document.body.appendChild(box);

            setTimeout(() => {
                box.style.right = '0';
            }, 50);

            window.closeEmailBox = function() {
                const box = document.querySelector('.email-share-box');
                if (box) {
                    box.style.right = '-400px';
                    setTimeout(() => box.remove(), 300);
                }
            };

            window.sendToSelf = function() {
                const pdfData = doc.output('datauristring');
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'send_program_email',
                        pdf_data: pdfData,
                        recipient_emails: ['" . esc_js($user_email) . "'],
                        is_self: true,
                        nonce: '" . wp_create_nonce('send_program_email') . "'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Programme envoyé avec succès !');
                        } else {
                            alert('Erreur lors de l\\'envoi du programme');
                        }
                    },
                    error: function() {
                        alert('Erreur lors de l\\'envoi du programme');
                    }
                });
            };

            window.addEmailInput = function() {
                const container = document.getElementById('email-inputs');
                const inputs = container.getElementsByTagName('input');
                if (inputs.length < 5) {
                    const input = document.createElement('input');
                    input.type = 'email';
                    input.placeholder = 'Adresse email';
                    input.className = 'share-email-input';
                    input.style.cssText = 'width: 100%; padding: 8px; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 4px;';
                    container.appendChild(input);
                }
            };

            window.sendEmailToPeople = function() {
                const inputs = document.getElementsByClassName('share-email-input');
                const emails = Array.from(inputs)
                    .map(input => input.value.trim())
                    .filter(email => email !== '' && email !== '" . esc_js($user_email) . "');

                if (emails.length === 0) {
                    alert('Veuillez entrer au moins une adresse email');
                    return;
                }

                const pdfData = doc.output('datauristring');

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'send_program_email',
                        pdf_data: pdfData,
                        recipient_emails: emails,
                        is_self: false,
                        nonce: '" . wp_create_nonce('send_program_email') . "'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Programme envoyé avec succès !');
                            closeEmailBox();
                        } else {
                            alert('Erreur lors de l\\'envoi du programme');
                        }
                    },
                    error: function() {
                        alert('Erreur lors de l\\'envoi du programme');
                    }
                });
            };
        }
    ";
    wp_add_inline_script('jspdf', $email_script);

    $styles = "
        .share-email-input:focus {
            outline: 2px solid #1B8C8E;
            border-color: #1B8C8E;
        }

        .email-share-box .close-button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px;
            color: #666;
            padding: 5px;
            width: 30px;
            height: 30px;
            line-height: 20px;
            text-align: center;
            transition: color 0.3s ease;
        }

        .email-share-box .close-button:hover {
            color: #1B8C8E;
        }

        .email-section {
            margin-bottom: 20px;
        }
    ";
    wp_add_inline_style('your-theme-style', $styles);
}
add_action('wp_enqueue_scripts', 'init_email_sender');

function handle_program_email() {
    check_ajax_referer('send_program_email', 'nonce');
    
    $pdf_data = $_POST['pdf_data'];
    $recipient_emails = $_POST['recipient_emails'];
    $is_self_email = isset($_POST['is_self']) && $_POST['is_self'] === 'true'; // Modifié ici
    $pdf_binary = base64_decode(explode(',', $pdf_data)[1]);
    
    $upload_dir = wp_upload_dir();
    $pdf_path = $upload_dir['path'] . '/programme-' . time() . '.pdf';
    file_put_contents($pdf_path, $pdf_binary);

    // Template pour l'email envoyé à soi-même
    $self_email_template = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: "Quicksand", Arial, sans-serif;
            }
            .header {
                background-color: #1B8C8E;
                padding: 20px;
                text-align: center;
            }
            .header img {
                max-width: 150px;
                height: auto;
            }
            .content {
                padding: 40px 20px;
                max-width: 600px;
                margin: 0 auto;
            }
            h1 {
                color: #333333;
                font-size: 24px;
                margin-bottom: 20px;
                font-weight: 600;
            }
            p {
                color: #666666;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 20px;
            }
            .footer {
                margin-top: 40px;
                text-align: center;
                color: #666666;
                font-size: 14px;
                line-height: 1.6;
            }
            .footer a {
                color: #1B8C8E;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="https://kwiik.travel/wp-content/uploads/2024/11/logo-menu.png" alt="Kwiik Travel" style="max-width: 150px;">
        </div>
        
        <div class="content">
            <h1>Votre programme de voyage personnalisé</h1>
            <p>Vous trouverez ci-joint votre programme de voyage créé avec Kwiik Travel.</p>
            <p>N\'hésitez pas à le partager avec vos proches !</p>
            
            <div class="footer">
                <p>Si vous avez des questions par rapport à votre programme, contactez Creative Slashers.<br>
                <a href="mailto:kwiik@creativeslashers.com">kwiik@creativeslashers.com</a></p>
                <p style="font-size: 12px; color: #999999;">Creative Slashers - 9 rue Mademoiselle, 75015 Paris</p>
            </div>
        </div>
    </body>
    </html>';

    // Template pour l'email envoyé aux amis (reste inchangé)
    $share_email_template = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: "Quicksand", Arial, sans-serif;
            }
            .header {
                background-color: #1B8C8E;
                padding: 20px;
                text-align: center;
            }
            .header img {
                max-width: 150px;
                height: auto;
            }
            .content {
                padding: 40px 20px;
                max-width: 600px;
                margin: 0 auto;
            }
            h1 {
                color: #333333;
                font-size: 24px;
                margin-bottom: 20px;
                font-weight: 600;
            }
            p {
                color: #666666;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 20px;
            }
            .button {
                display: inline-block;
                background-color: #1B8C8E;
                color: white;
                padding: 12px 25px;
                text-decoration: none;
                margin-top: 20px;
                font-weight: 500;
            }
            .footer {
                margin-top: 40px;
                text-align: center;
                color: #666666;
                font-size: 14px;
                line-height: 1.6;
            }
            .footer a {
                color: #1B8C8E;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="https://kwiik.travel/wp-content/uploads/2024/11/logo-menu.png" alt="Kwiik Travel" style="max-width: 150px;">
        </div>
        
        <div class="content">
            <h1>Un ami partage son programme de voyage avec vous !</h1>
            <p>Découvrez ce programme de voyage personnalisé créé avec Kwiik Travel.</p>
            <p>Vous aussi, créez votre programme de voyage sur mesure :</p>
            <a href="https://kwiik.travel" class="button">Découvrir Kwiik Travel</a>
            
            <div class="footer">
                <p>Si vous avez des questions par rapport à votre programme, contactez Creative Slashers.<br>
                <a href="mailto:kwiik@creativeslashers.com">kwiik@creativeslashers.com</a></p>
                <p style="font-size: 12px; color: #999999;">Creative Slashers - 9 rue Mademoiselle, 75015 Paris</p>
            </div>
        </div>
    </body>
    </html>';

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Creative Slashers <kwiik@creativeslashers.com>'
    );

    $success = true;
    foreach ($recipient_emails as $email) {
        $email = sanitize_email($email);
        if ($email) {
            if ($is_self_email) {
                $subject = 'Votre programme de voyage Kwiik Travel';
                $template = $self_email_template;
            } else {
                $subject = 'Un ami partage son programme de voyage avec vous !';
                $template = $share_email_template;
            }

            $mail_sent = wp_mail($email, $subject, $template, $headers, array($pdf_path));
            if (!$mail_sent) {
                $success = false;
            }
        }
    }
    
    unlink($pdf_path);
    
    wp_send_json_success(array('sent' => $success));
}

add_action('wp_ajax_send_program_email', 'handle_program_email');
add_action('wp_ajax_nopriv_send_program_email', 'handle_program_email');