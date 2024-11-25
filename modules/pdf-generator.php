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

            // Fonction pour ajouter l'en-tête
            function addHeader(pageNumber) {
                if (pageNumber > 1) { // N'ajoute l'en-tête qu'à partir de la page 2
                    doc.addImage('https://kwiik.travel/wp-content/uploads/2024/11/logo-menu.png', 'PNG', 10, 10, 20, 7);
                    doc.setDrawColor(27, 140, 142);
                    doc.setLineWidth(0.5);
                    doc.line(10, 20, 200, 20);
                }
            }

            // Fonction pour ajouter le pied de page
            function addFooter(pageNumber) {
                if (pageNumber > 1) { // N'ajoute le pied de page qu'à partir de la page 2
                    doc.setFontSize(8);
                    doc.setTextColor(100, 100, 100);
                    doc.text('Kwiik Travel - Programme de voyage personnalisé', 105, 285, { align: 'center' });
                    doc.text('www.kwiik.travel', 105, 290, { align: 'center' });
                    doc.text('Page ' + pageNumber, 190, 290, { align: 'right' });
                }
            }

            // Fonction pour ajouter un encadré
            function addBox(title, content, y, color = '#f5f5f5') {
                const x = 20;
                const width = 170;
                const lineHeight = 7;
                const lines = doc.splitTextToSize(content, width - 10);
                const height = (lines.length + 1) * lineHeight + 10;

                doc.setFillColor(color);
                doc.rect(x, y, width, height, 'F');
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(27, 140, 142);
                doc.text(title, x + 5, y + 10);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(0, 0, 0);
                lines.forEach((line, index) => {
                    doc.text(line, x + 5, y + 20 + (index * lineHeight));
                });

                return height;
            }

            let currentPage = 1;

            // Page de couverture modifiée
            doc.addImage('https://kwiik.travel/wp-content/uploads/2024/11/logo-menu.png', 'PNG', 85, 40, 40, 14);

            // Récupérer la destination
            const destinationElement = document.querySelector('textarea[name=\"DESTINATION\"]');
            const destination = destinationElement ? destinationElement.value : '';

            // Titre avec la destination
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(28);
            doc.setTextColor(27, 140, 142);
            doc.text('Votre voyage à', 105, 120, { align: 'center' });
            doc.text(destination + ' !', 105, 140, { align: 'center' });

            // Footer avec Creative Slashers
            doc.setFontSize(10);
            doc.setTextColor(100, 100, 100);
            doc.text('Kwiik est un produit Creative Slashers', 105, 270, { align: 'center' });

            // Page des informations importantes
            doc.addPage();
            currentPage++;
            addHeader(currentPage);

            let yPosition = 40;
            const emergencyContent = 'Police: 17\\nSAMU: 15\\nPompiers: 18\\nNuméro d\\'urgence européen: 112\\nKwiik Travel: +33 (0)1 XX XX XX XX';
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
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(16);
            doc.setTextColor(27, 140, 142);
            doc.text('Votre Programme', 20, 40);

            // Contenu du programme
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(11);
            doc.setTextColor(0, 0, 0);
            yPosition = 50;

            const contentLines = doc.splitTextToSize(text, 170);
            contentLines.forEach(line => {
                if (yPosition > 250) {
                    doc.addPage();
                    currentPage++;
                    addHeader(currentPage);
                    addFooter(currentPage);
                    yPosition = 40;
                }
                doc.text(line, 20, yPosition);
                yPosition += 6;
            });

            addFooter(currentPage);

            return doc;
        }

        function downloadPDF() {
            const doc = generatePDF();
            if (!doc) {
                alert('Veuillez d\\'abord générer un programme');
                return;
            }
            doc.save('Programme-Voyage-Kwiik-Travel.pdf');
            
            // Ajouter un appel pour générer le TXT
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
    
    // Ajouter la variable ajaxurl pour JavaScript
    wp_localize_script('jquery', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'init_pdf_generator');