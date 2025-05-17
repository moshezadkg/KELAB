<?php
header('Content-Type: application/json; charset=utf-8');

// בדיקה שהבקשה היא מסוג POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // כתובת המייל של המקבל
    $to = "h0527104792@gmail.com";
    
    // הגדרות מיילים נוספות
    $from = "yearim@yearim-club.co.il";
    $form_type = isset($_POST['form_type']) ? $_POST['form_type'] : "טופס יצירת קשר";
    
    // הגדרת כותרת המייל לפי סוג הטופס
    if ($form_type == "swimming") {
        $subject = "הרשמה לבית הספר לשחייה - קאנטרי יערים קלאב";
    } else {
        $subject = "פנייה חדשה מאתר קאנטרי יערים קלאב";
    }
    
    // איסוף נתונים מהטופס
    $fields = array();
    foreach ($_POST as $key => $value) {
        if ($key != 'form_type') { // לא כולל את סוג הטופס בהודעה
            $fields[$key] = htmlspecialchars($value);
        }
    }
    
    // הגדרת צבעים וסגנון לפי עיצוב האתר (מלון קאנטרי יערים קלאב)
    $primary_color = "#0e4a5f"; // טורקיז כהה
    $secondary_color = "#d4af37"; // גוון זהב
    $accent_color = "#e3f2fd"; // כחול בהיר
    $dark_color = "#0e4a5f"; // כהה לטקסט
    $light_color = "#ffffff"; // לבן
    $gradient_start = "#0e4a5f"; // טורקיז כהה
    $gradient_end = "#0a3444"; // טורקיז עמוק יותר
    $whatsapp_color = "#25d366"; // צבע וואטסאפ

    // שינוי צבעים לפי סוג הטופס (אופציונלי)
    if ($form_type == "swimming") {
        $accent_color = "#e0f7fa";
    }
    
    // קישור ללוגו
    $logo_url = "תמונות/לוגו.png"; // הלוגו של הקאנטרי
    
    // מספר טלפון ווואטסאפ
    $phone_number = "02-5953535";
    $whatsapp_number = "052-7104792";
    $whatsapp_intl = "972527104792"; // מספר בינלאומי לקישור וואטסאפ
    
    // הכנת הודעת HTML מעוצבת
    $message_html = '
    <!DOCTYPE html>
    <html dir="rtl" lang="he">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            @import url(https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;700&display=swap);
            
            body {
                font-family: \'Heebo\', Arial, sans-serif;
                background-color: #f7f7f7;
                color: #333;
                line-height: 1.6;
                direction: rtl;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 650px;
                margin: 20px auto;
                background-color: #fff;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            }
            .header {
                background-color: ' . $primary_color . ';
                color: white;
                padding: 30px 0;
                text-align: center;
                position: relative;
            }
            .header-bg {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(145deg, ' . $gradient_start . ' 30%, ' . $gradient_end . ' 100%);
                opacity: 1;
            }
            .logo-container {
                display: block;
                text-align: center;
                position: relative;
                z-index: 2;
                padding: 10px 0 20px;
            }
            .logo {
                width: 150px;
                height: auto;
                border-radius: 50%;
            }
            .header-content {
                position: relative;
                z-index: 2;
                padding: 0 20px;
            }
            .header h1 {
                margin: 0;
                font-size: 26px;
                font-weight: 700;
                color: ' . $light_color . ';
            }
            .header-date {
                margin: 10px 0 0;
                font-size: 14px;
                opacity: 0.8;
                color: ' . $light_color . ';
                font-weight: 300;
            }
            .content {
                padding: 35px;
            }
            .intro {
                background-color: ' . $accent_color . ';
                padding: 20px 25px;
                border-radius: 12px;
                margin-bottom: 30px;
                border-right: 4px solid ' . $primary_color . ';
                box-shadow: 0 3px 10px rgba(0,0,0,0.03);
            }
            .intro p {
                margin: 0;
                font-size: 16px;
                line-height: 1.7;
            }
            .section-title {
                font-size: 18px;
                font-weight: 700;
                margin-bottom: 20px;
                color: ' . $dark_color . ';
                padding-bottom: 10px;
                border-bottom: 2px solid ' . $secondary_color . ';
                position: relative;
            }
            .section-title:after {
                content: "";
                position: absolute;
                right: 0;
                bottom: -2px;
                width: 70px;
                height: 2px;
                background-color: ' . $primary_color . ';
            }
            .form-details {
                background-color: #fff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 3px 20px rgba(0,0,0,0.08);
                margin-bottom: 30px;
            }
            .form-title {
                background: linear-gradient(to left, ' . $primary_color . ', ' . $gradient_end . ');
                color: white;
                padding: 18px 25px;
                font-size: 18px;
                font-weight: 500;
                display: flex;
                align-items: center;
            }
            .form-title i {
                margin-left: 12px;
                font-size: 20px;
            }
            .fields-container {
                padding: 10px 0;
            }
            .field {
                margin-bottom: 0;
                padding: 15px 25px;
                border-bottom: 1px solid #eaeaea;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
            }
            .field:last-child {
                border-bottom: none;
            }
            .field:nth-child(odd) {
                background-color: #fafafa;
            }
            .field-name {
                font-weight: 500;
                color: ' . $primary_color . ';
                width: 140px;
                padding-left: 15px;
            }
            .field-value {
                flex: 1;
                min-width: 200px;
                font-weight: 400;
            }
            .divider {
                height: 3px;
                background: linear-gradient(to right, ' . $primary_color . ', ' . $secondary_color . ');
                margin: 35px 0 25px;
                border-radius: 3px;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding: 35px 30px;
                background-color: #f8f8f8;
                color: #666;
                font-size: 14px;
                border-top: 1px solid #eee;
            }
            .footer-logo {
                width: 80px;
                height: auto;
                margin-bottom: 15px;
                opacity: 0.9;
            }
            .contact-info {
                margin: 20px 0;
            }
            .contact-btn {
                display: inline-block;
                margin: 10px;
                padding: 12px 25px;
                background-color: ' . $primary_color . ';
                color: white;
                text-decoration: none;
                border-radius: 50px;
                font-weight: 500;
                box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            }
            .contact-btn.whatsapp {
                background-color: ' . $whatsapp_color . ';
            }
            .contact-btn i {
                margin-left: 8px;
                font-size: 18px;
                vertical-align: middle;
            }
            .copyright {
                margin-top: 20px;
                font-size: 12px;
                color: #999;
                font-weight: 300;
            }
            .highlight {
                color: ' . $secondary_color . ';
                font-weight: 700;
            }
            .social-links {
                margin: 25px 0 15px;
                display: flex;
                justify-content: center;
                gap: 10px;
            }
            .social-links a {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                background-color: #1c6da3;
                border-radius: 50%;
                color: white;
                text-decoration: none;
                font-size: 16px;
                transition: transform 0.3s;
            }
            .social-links a:hover {
                transform: scale(1.1);
            }
            
            @media screen and (max-width: 600px) {
                .container {
                    width: 100%;
                    margin: 0;
                    border-radius: 0;
                }
                .content {
                    padding: 25px 20px;
                }
                .field {
                    padding: 15px;
                }
                .field-name {
                    width: 100%;
                    padding-bottom: 8px;
                }
                .field-value {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="header-bg"></div>
                <div class="logo-container">
                    <img src="' . $logo_url . '" alt="יערים קלאב" class="logo">
                </div>
                <div class="header-content">
                    <h1>' . $subject . '</h1>
                    <p class="header-date">' . date('d/m/Y H:i') . '</p>
                </div>
            </div>
            
            <div class="content">
                <div class="intro">
                    <p><strong>שלום רב,</strong></p>
                    <p>התקבלה פנייה חדשה דרך ';
                    
    // הוספת תיאור סוג הטופס
    if ($form_type == "swimming") {
        $message_html .= 'טופס ההרשמה לבית הספר לשחייה באתר קאנטרי יערים קלאב.';
    } else {
        $message_html .= 'טופס יצירת הקשר באתר קאנטרי יערים קלאב.';
    }
    
    $message_html .= '</p>
                </div>
                
                <h2 class="section-title">פרטי הפנייה</h2>
                
                <div class="form-details">
                    <div class="form-title">';
                    
    // כותרת הפרטים לפי סוג הטופס
    if ($form_type == "swimming") {
        $message_html .= '<i>🏊‍♂️</i> פרטי הרשמה לבית הספר לשחייה';
    } else {
        $message_html .= '<i>✉️</i> פרטי הפנייה';
    }
    
    $message_html .= '</div>
                    <div class="fields-container">';
    
    // עדיפות לשדות חשובים - להציג בראש הרשימה
    $primary_fields = array('fullName', 'childName', 'phone', 'email');
    $processed_fields = array();
    
    // קודם מציגים את השדות החשובים
    foreach ($primary_fields as $key) {
        if (isset($fields[$key])) {
            $field_name = '';
            
            // המרת שמות השדות לעברית
            switch($key) {
                case 'fullName': $field_name = 'שם מלא'; break;
                case 'childName': $field_name = 'שם הילד/ה'; break;
                case 'phone': $field_name = 'טלפון'; break;
                case 'email': $field_name = 'דואר אלקטרוני'; break;
            }
            
            $message_html .= '
                <div class="field">
                    <div class="field-name">' . $field_name . ':</div>
                    <div class="field-value"><strong>' . nl2br($fields[$key]) . '</strong></div>
                </div>';
            
            $processed_fields[] = $key;
        }
    }
    
    // אחר כך מציגים את שאר השדות
    foreach($fields as $key => $value) {
        if (!in_array($key, $processed_fields)) {
            $field_name = '';
            
            // המרת שמות השדות לעברית
            switch($key) {
                case 'childAge': $field_name = 'גיל הילד/ה'; break;
                case 'preferredDay': $field_name = 'יום מועדף'; break;
                case 'preferredTime': $field_name = 'שעה מועדפת'; break;
                case 'swimmingLevel': $field_name = 'רמת שחייה'; break;
                case 'subject': $field_name = 'נושא הפנייה'; break;
                case 'message': $field_name = 'הודעה'; break;
                case 'newsletter': $field_name = 'רוצה לקבל עדכונים'; break;
                default: $field_name = $key; break;
            }
            
            $message_html .= '
                <div class="field">
                    <div class="field-name">' . $field_name . ':</div>
                    <div class="field-value">' . nl2br($value) . '</div>
                </div>';
        }
    }
    
    $message_html .= '
                    </div>
                </div>
                
                <div class="divider"></div>
            </div>
            
            <div class="footer">
                <img src="' . $logo_url . '" alt="יערים קלאב" class="footer-logo">
                <p><strong class="highlight">קאנטרי יערים קלאב</strong></p>
                <div class="social-links">
                    <a href="tel:' . preg_replace('/[^0-9]/', '', $phone_number) . '" title="חייגו אלינו"><i class="fas fa-phone-alt"></i></a>
                    <a href="https://wa.me/' . $whatsapp_intl . '" title="וואטסאפ" class="whatsapp" style="background-color: #25d366;"><i class="fab fa-whatsapp"></i></a>
                    <a href="https://www.facebook.com/countryyearim/?locale=he_IL" title="דף הפייסבוק שלנו" target="_blank"><i class="fab fa-facebook-f"></i></a>
                </div>
                
                <p class="copyright">© ' . date('Y') . ' קאנטרי יערים קלאב. כל הזכויות שמורות.</p>
            </div>
        </div>
    </body>
    </html>';
    
    // גרסת טקסט רגיל של ההודעה
    $message_text = "התקבלה פנייה חדשה מאתר קאנטרי יערים קלאב\n";
    $message_text .= "========================================\n\n";
    
    if ($form_type == "swimming") {
        $message_text .= "פרטי הרשמה לבית הספר לשחייה:\n\n";
    } else {
        $message_text .= "פרטי הפנייה:\n\n";
    }
    
    foreach($fields as $key => $value) {
        $field_name = '';
        switch($key) {
            case 'fullName': $field_name = 'שם מלא'; break;
            case 'phone': $field_name = 'טלפון'; break;
            case 'email': $field_name = 'דואר אלקטרוני'; break;
            case 'message': $field_name = 'הודעה'; break;
            case 'childName': $field_name = 'שם הילד/ה'; break;
            case 'childAge': $field_name = 'גיל הילד/ה'; break;
            case 'preferredDay': $field_name = 'יום מועדף'; break;
            case 'preferredTime': $field_name = 'שעה מועדפת'; break;
            case 'swimmingLevel': $field_name = 'רמת שחייה'; break;
            case 'subject': $field_name = 'נושא הפנייה'; break;
            case 'newsletter': $field_name = 'רוצה לקבל עדכונים'; break;
            default: $field_name = $key; break;
        }
        $message_text .= $field_name . ": " . $value . "\n";
    }
    
    $message_text .= "\n========================================\n";
    $message_text .= "קאנטרי יערים קלאב\n";
    $message_text .= "טלפון: " . $phone_number . "\n";
    $message_text .= "וואטסאפ: " . $whatsapp_number . "\n";
    
    // כותרות המייל
    $headers = "From: קאנטרי יערים קלאב <" . $from . ">\r\n";
    $headers .= "Reply-To: " . (isset($fields['email']) ? $fields['email'] : $from) . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"boundary\"\r\n";

    // בניית גוף ההודעה (מכיל גם גרסת טקסט וגם HTML)
    $body = "--boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $message_text . "\r\n\r\n";
    $body .= "--boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $message_html . "\r\n\r\n";
    $body .= "--boundary--";
    
    // שליחת המייל
    $mailSent = mail($to, $subject, $body, $headers);
    
    if ($mailSent) {
        echo json_encode(["status" => "success", "message" => "ההודעה נשלחה בהצלחה"]);
    } else {
        echo json_encode(["status" => "error", "message" => "אירעה שגיאה בשליחת ההודעה"]);
    }
    exit;
}

// אם הגענו לכאן, הבקשה לא הייתה תקינה
echo json_encode(["status" => "error", "message" => "בקשה לא תקינה"]);
exit;
?>
