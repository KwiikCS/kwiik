<?php
function init_pdf_generator() {
    $pdf_script = "
        window.jsPDF = window.jspdf.jsPDF;
        
        function generatePDF() {
            const text = document.querySelector('.wp-block-ai-engine-form-output').innerText;
            if (!text || text.trim() === '') {
                return null;
            }
            
            const doc = new jsPDF({
                orientation: 'p',
                unit: 'mm',
                format: 'a4'
            });

            // Ajout de la police Quicksand
            doc.addFont('https://kwiik.travel/wp-content/uploads/2024/03/Quicksand-VariableFont_wght.ttf', 'Quicksand', 'normal');
            doc.setFont('Quicksand', 'normal');

            function addHeader(pageNumber) {
                if (pageNumber > 1) {
                    doc.addImage('https://kwiik.travel/wp-content/uploads/2024/03/kwiik-1.png', 'PNG', 10, 10, 50, 13.9);
                    doc.setDrawColor(27, 140, 142);
                    doc.setLineWidth(0.5);
                    doc.line(10, 30, 200, 30);
                }
            }

            function addFooter(pageNumber) {
                if (pageNumber > 1) {
                    doc.setFontSize(8);
                    doc.setTextColor(27, 140, 142);
                    doc.text('Kwiik Travel - Programme de voyage personnalisé', 105, 285, { align: 'center' });
                    doc.text('www.kwiik.travel', 105, 290, { align: 'center' });
                    doc.text('Page ' + pageNumber, 190, 290, { align: 'right' });
                }
            }

            function addBox(title, content, y, color = '#f0f7f7') {
                const x = 20;
                const width = 170;
                const lineHeight = 7;
                const lines = doc.splitTextToSize(content, width - 10);
                const height = (lines.length + 1) * lineHeight + 10;

                doc.setFillColor(color);
                doc.rect(x, y, width, height, 'F');
                doc.setTextColor(27, 140, 142);
                doc.text(title, x + 5, y + 10);
                doc.setTextColor(51, 51, 51);
                lines.forEach((line, index) => {
                    doc.text(line, x + 5, y + 20 + (index * lineHeight));
                });

                return height;
            }

            let currentPage = 1;

            // Page de couverture
            doc.addImage('https://kwiik.travel/wp-content/uploads/2024/03/kwiik-1.png', 'PNG', 55, 40, 100, 27.8);

            const destinationElement = document.querySelector('textarea[name=\"DESTINATION\"]');
            const destination = destinationElement ? destinationElement.value : '';

            // Titre avec la destination
            doc.setFontSize(28);
            doc.setTextColor(27, 140, 142);
            doc.text('Votre voyage à', 105, 120, { align: 'center' });
            doc.text(destination + ' !', 105, 140, { align: 'center' });

            // Footer avec Creative Slashers et logo
            doc.setFontSize(10);
            doc.setTextColor(27, 140, 142);
            doc.text('Kwiik est un produit Creative Slashers', 105, 260, { align: 'center' });
            doc.addImage('https://kwiik.travel/wp-content/uploads/2024/11/logo-cs.png', 'PNG', 90, 265, 25, 14);
            doc.setFontSize(8);
            doc.text('Creative Slashers - 9 rue mademoiselle, 75015 Paris - kwiik@creativeslashers.com', 105, 285, { align: 'center' });

            // Page des informations importantes
            doc.addPage();
            currentPage++;
            addHeader(currentPage);

            let yPosition = 40;
            const emergencyContent = 'Police: 17\\nSAMU: 15\\nPompiers: 18\\nNuméro d\\'urgence européen: 112\\nEmail: kwiik@creativeslashers.com';
            const tipsContent = 'Gardez une copie de vos documents\\nRestez hydraté\\nRespectez les coutumes locales\\nGardez ce programme avec vous';
            
            yPosition += addBox('Contacts d\\'Urgence', emergencyContent, yPosition);
            yPosition += 10;
            addBox('Conseils Pratiques', tipsContent, yPosition);

            addFooter(currentPage);

            // Page du programme
            doc.addPage();
            currentPage++;
            addHeader(currentPage);

            // Titre du programme
            doc.setFontSize(16);
            doc.setTextColor(27, 140, 142);
            doc.text('Votre Programme', 20, 40);

            // Important : réinitialiser la couleur ET la taille pour le contenu
            doc.setFontSize(11);
            doc.setTextColor(51, 51, 51);
            yPosition = 50;

            const contentLines = doc.splitTextToSize(text, 170);
            contentLines.forEach(line => {
                if (yPosition > 250) {
                    doc.addPage();
                    currentPage++;
                    addHeader(currentPage);
                    addFooter(currentPage);
                    yPosition = 40;
                    // Important : réinitialiser la couleur ET la taille après chaque nouvelle page
                    doc.setFontSize(11);
                    doc.setTextColor(51, 51, 51);
                }
                doc.text(line, 20, yPosition);
                yPosition += 6;
            });

            addFooter(currentPage);

            // Page de fin
            doc.addPage();
            doc.addImage('https://kwiik.travel/wp-content/uploads/2024/03/kwiik-1.png', 'PNG', 55, 40, 100, 27.8);

            doc.setFontSize(28);
            doc.setTextColor(27, 140, 142);
            doc.text('Bon voyage à', 105, 120, { align: 'center' });
            doc.text(destination + ' !', 105, 140, { align: 'center' });

            doc.setFontSize(10);
            doc.text('Kwiik est un produit Creative Slashers', 105, 260, { align: 'center' });
            doc.addImage('https://kwiik.travel/wp-content/uploads/2024/11/logo-cs.png', 'PNG', 90, 265, 25, 14);
            doc.setFontSize(8);
            doc.text('Creative Slashers - 9 rue mademoiselle, 75015 Paris - kwiik@creativeslashers.com', 105, 285, { align: 'center' });

            return doc;
        }

        function downloadPDF() {
            const doc = generatePDF();
            if (!doc) {
                alert('Veuillez d\\'abord générer un programme');
                return;
            }
            doc.save('Programme-Voyage-Kwiik-Travel.pdf');
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'generate_programme_txt',
                    destination: document.querySelector('textarea[name=\"DESTINATION\"]').value,
                    content: document.querySelector('#mwai-31u58kxmt').innerText
                },
                success: function(response) {
                    console.log('TXT généré avec succès', response);
                },
                error: function(error) {
                    console.error('Erreur lors de la génération TXT', error);
                }
            });
        }
    ";
    wp_add_inline_script('jspdf', $pdf_script);
    wp_localize_script('jquery', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'init_pdf_generator');
