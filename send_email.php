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
    
    // הכנת הודעת HTML מעוצבת
    $message_html = '
    <!DOCTYPE html>
    <html dir="rtl">
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f5f5f5;
                color: #333;
                line-height: 1.6;
                direction: rtl;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            }
            .header {
                background-color: #1c6da3;
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 8px 8px 0 0;
                margin-bottom: 20px;
            }
            .content {
                padding: 20px;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #eee;
                color: #777;
                font-size: 12px;
            }
            h1 {
                margin: 0;
                font-size: 24px;
            }
            .field {
                margin-bottom: 15px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }
            .field-name {
                font-weight: bold;
                color: #1c6da3;
            }
            .field-value {
                margin-top: 5px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . $subject . '</h1>
            </div>
            <div class="content">';
            
    // הוספת תוכן ההודעה
    $message_html .= '<p>התקבלה פנייה חדשה מאתר קאנטרי יערים קלאב.</p>';
    
    foreach($fields as $key => $value) {
        $field_name = '';
        
        // המרת שמות השדות לעברית
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
            default: $field_name = $key; break;
        }
        
        $message_html .= '
            <div class="field">
                <div class="field-name">' . $field_name . ':</div>
                <div class="field-value">' . nl2br($value) . '</div>
            </div>';
    }
    
    $message_html .= '
            </div>
            <div class="footer">
                <p>© 2025 קאנטרי יערים קלאב. כל הזכויות שמורות.</p>
            </div>
        </div>
    </body>
    </html>';
    
    // גרסת טקסט רגיל של ההודעה
    $message_text = "התקבלה פנייה חדשה מאתר קאנטרי יערים קלאב:\n\n";
    
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
            default: $field_name = $key; break;
        }
        $message_text .= $field_name . ": " . $value . "\n";
    }
    
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
