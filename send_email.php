<?php
header('Content-Type: application/json; charset=utf-8');

// מניעת גישה ישירה לקובץ
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    // אם מישהו מנסה לגשת ישירות לקובץ, לא ל-API
    http_response_code(403); // Forbidden
    echo json_encode(["status" => "error", "message" => "גישה ישירה לקובץ אסורה"]);
    exit;
}

// זיהוי Referer לאבטחה נוספת
$allowed_referers = ['http://localhost', 'https://localhost', 'https://yearim-club.co.il', 'http://yearim-club.co.il'];
$is_valid_referer = false;

if (isset($_SERVER['HTTP_REFERER'])) {
    foreach ($allowed_referers as $referer) {
        if (strpos($_SERVER['HTTP_REFERER'], $referer) === 0) {
            $is_valid_referer = true;
            break;
        }
    }
}

if (!$is_valid_referer && !empty($_SERVER['HTTP_REFERER'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "מקור לא מורשה"]);
    exit;
}

// הגנת Rate Limiting - הגבלת מספר הבקשות מאותה כתובת IP
function checkRateLimit($ip) {
    $log_file = "rate_limit.json";
    $max_requests = 10; // מספר בקשות מקסימלי מאותה כתובת IP
    $timeframe = 3600; // מסגרת זמן בשניות (שעה)
    
    // יצירת או טעינת קובץ היסטוריית בקשות
    if (file_exists($log_file)) {
        $requests_log = json_decode(file_get_contents($log_file), true);
    } else {
        $requests_log = [];
    }
    
    // ניקוי רשומות ישנות
    $now = time();
    foreach ($requests_log as $request_ip => $timestamps) {
        $requests_log[$request_ip] = array_filter($timestamps, function($timestamp) use ($now, $timeframe) {
            return ($now - $timestamp) < $timeframe;
        });
        
        // הסרת כתובות IP ללא רשומות
        if (empty($requests_log[$request_ip])) {
            unset($requests_log[$request_ip]);
        }
    }
    
    // הוספת הבקשה הנוכחית
    if (!isset($requests_log[$ip])) {
        $requests_log[$ip] = [];
    }
    
    // בדיקת מספר הבקשות
    if (count($requests_log[$ip]) >= $max_requests) {
        // חריגה ממגבלת הבקשות
        log_security_event("Rate limit exceeded", $ip, "Requests: " . count($requests_log[$ip]));
        return false;
    }
    
    // עדכון רשימת הבקשות
    $requests_log[$ip][] = $now;
    file_put_contents($log_file, json_encode($requests_log));
    
    return true;
}

// בדיקת Rate Limiting לפני המשך טיפול בבקשה
if (!checkRateLimit($_SERVER['REMOTE_ADDR'])) {
    http_response_code(429); // Too Many Requests
    echo json_encode(["status" => "error", "message" => "נשלחו יותר מדי בקשות. נא לנסות שוב מאוחר יותר."]);
    exit;
}

// בדיקה שהבקשה היא מסוג POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // בדיקת honeypot - אם יש בו ערך זה כנראה בוט
    if (isset($_POST['check_bot']) && !empty($_POST['check_bot'])) {
        log_security_event("Bot detection triggered", $_SERVER['REMOTE_ADDR'], "Honeypot field filled");
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "חשד לפעילות אוטומטית"]);
        exit;
    }
    
    // בדיקת טוקן CSRF
    if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token'])) {
        log_security_event("Missing CSRF token", $_SERVER['REMOTE_ADDR']);
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "בקשה לא מאובטחת"]);
        exit;
    }
    
    // אימות תקינות הטוקן - בדיקה בסיסית שהוא מכיל timestamp והוא לא ישן מדי
    $csrf_parts = explode('-', $_POST['csrf_token']);
    if (count($csrf_parts) !== 2) {
        log_security_event("Invalid CSRF token format", $_SERVER['REMOTE_ADDR'], $_POST['csrf_token']);
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "טוקן אבטחה לא תקין"]);
        exit;
    }
    
    // בדיקה שהטוקן לא ישן מדי (לא יותר משעה)
    $csrf_time = intval($csrf_parts[0]);
    $current_time = time() * 1000; // convert to milliseconds to match JS timestamp
    if (($current_time - $csrf_time) > 3600000) { // 1 hour in milliseconds
        log_security_event("Expired CSRF token", $_SERVER['REMOTE_ADDR'], $_POST['csrf_token']);
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "תוקף הבקשה פג, נא לרענן את הדף ולנסות שוב"]);
        exit;
    }
    
    // בדיקה שיש סוג טופס
    if (!isset($_POST['form_type'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "חסר סוג טופס"]);
        exit;
    }
    
    $form_type = $_POST['form_type'];
    $to = "kantri360a@gmail.com"; // כתובת האימייל אליה תישלח ההודעה
    $from = "noreply@yearim-club.co.il"; // כתובת השולח
    
    // נושא ההודעה לפי סוג הטופס
    if ($form_type == "swimming") {
        $subject = "הרשמה לבית הספר לשחייה - קאנטרי יערים קלאב";
    } else {
        $subject = "פנייה חדשה מאתר קאנטרי יערים קלאב";
    }
    
    // איסוף נתונים מהטופס - עם סניטיזציה מתקדמת
    $fields = array();
    $required_fields = ['fullName', 'phone', 'email'];
    $missing_fields = [];
    
    // וידוא שכל השדות הנדרשים קיימים
    if ($form_type == "swimming") {
        $required_fields[] = 'childName';
        $required_fields[] = 'childAge';
        $required_fields[] = 'swimmingLevel';
    }
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "חסרים שדות חובה: " . implode(", ", $missing_fields)]);
        exit;
    }
    
    // וידוא שהאימייל תקין
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "כתובת האימייל אינה תקינה"]);
        exit;
    }
    
    // וידוא שמספר הטלפון תקין
    if (!preg_match('/^0\d{1,2}[-\s]?\d{3}[-\s]?\d{4}$/', $_POST['phone'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "מספר הטלפון אינו תקין"]);
        exit;
    }
    
    // סניטיזציה של כל השדות
    foreach ($_POST as $key => $value) {
        // לא כוללים את שדות האבטחה במייל
        if ($key != 'form_type' && $key != 'csrf_token' && $key != 'check_bot') {
            // סינון XSS פוטנציאלי
            $filtered_value = filter_var($value, FILTER_SANITIZE_STRING);
            $fields[$key] = htmlspecialchars($filtered_value);
        }
    }
    
    // הכנת הודעת HTML מעוצבת - תואם לעיצוב החדש מההדמיה
    $message_html = '
    <!DOCTYPE html>
    <html dir="rtl" lang="he">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>קאנטרי יערים קלאב</title>
        <!-- הוספת Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            @import url(https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;700&display=swap);
            
            body {
                font-family: \'Heebo\', Arial, sans-serif;
                background-color: #f7f7f7;
                color: #333;
                line-height: 1.6;
                direction: rtl;
                margin: 0;
                padding: 20px;
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
                background-color: #0e4a5f;
                color: white;
                padding: 15px;
                text-align: center;
                position: relative;
                display: flex;
                align-items: center;
                flex-direction: row-reverse;
            }
            .header-bg {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(145deg, #1c6da3 30%, #0d4e79 100%);
                opacity: 1;
            }
            .logo-container {
                position: relative;
                z-index: 2;
                margin-right: 15px;
                margin-left: 0;
                flex-shrink: 0;
            }
            .logo {
                width: 70px;
                height: auto;
                border-radius: 50%;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                border: 2px solid rgba(255,255,255,0.3);
            }
            .header-content {
                position: relative;
                z-index: 2;
                flex-grow: 1;
            }
            .header h1 {
                margin: 0;
                font-size: 20px;
                font-weight: 700;
                color: #ffffff;
            }
            .header-date {
                margin: 3px 0 0;
                font-size: 12px;
                opacity: 0.8;
                color: #ffffff;
                font-weight: 300;
            }
            .content {
                padding: 35px;
            }
            .intro {
                background-color: #f1f8fe;
                padding: 20px 25px;
                border-radius: 12px;
                margin-bottom: 30px;
                border-right: 4px solid #1c6da3;
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
                color: #1c6da3;
                padding-bottom: 10px;
                border-bottom: 2px solid #d4af37;
                position: relative;
            }
            .section-title:after {
                content: "";
                position: absolute;
                right: 0;
                bottom: -2px;
                width: 70px;
                height: 2px;
                background-color: #1c6da3;
            }
            .form-details {
                background-color: #fff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 3px 20px rgba(0,0,0,0.08);
                margin-bottom: 30px;
            }
            .form-title {
                background: linear-gradient(to left, #1c6da3, #0d4e79);
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
                background-color: #f9f9f9;
            }
            .field-name {
                font-weight: 500;
                color: #1c6da3;
                width: 140px;
                padding-left: 15px;
            }
            .field-value {
                flex: 1;
                min-width: 200px;
                font-weight: 400;
            }
            .recommendations {
                background-color: #f1f8e9;
                padding: 25px;
                border-radius: 12px;
                margin-top: 30px;
                position: relative;
                box-shadow: 0 3px 15px rgba(0,0,0,0.05);
                border-right: 4px solid #7cb342;
            }
            .recommendations h3 {
                color: #33691e;
                margin-top: 0;
                font-size: 18px;
                font-weight: 700;
                display: flex;
                align-items: center;
            }
            .recommendations h3 i {
                margin-left: 10px;
            }
            .recommendations ul {
                padding-right: 25px;
                margin-bottom: 0;
            }
            .recommendations li {
                margin-bottom: 10px;
                position: relative;
            }
            .recommendations li:last-child {
                margin-bottom: 0;
            }
            .divider {
                height: 3px;
                background: linear-gradient(to right, #1c6da3, #d4af37);
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
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
            .contact-btn {
                display: inline-block;
                padding: 12px 25px;
                background-color: #1c6da3;
                color: white;
                text-decoration: none;
                border-radius: 50px;
                font-weight: 500;
                transition: all 0.3s;
                box-shadow: 0 3px 10px rgba(0,0,0,0.1);
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .contact-btn.whatsapp {
                background-color: #25d366;
            }
            .contact-btn i {
                margin-left: 8px;
                vertical-align: middle;
                font-size: 18px;
            }
            .whatsapp-icon {
                display: inline-block;
                width: 20px;
                height: 20px;
                margin-left: 8px;
                vertical-align: middle;
            }
            .contact-btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.15);
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
            .copyright {
                margin-top: 20px;
                font-size: 12px;
                color: #999;
                font-weight: 300;
            }
            .highlight {
                color: #d4af37;
                font-weight: 700;
            }
            .button {
                display: inline-block;
                background: linear-gradient(to right, #1c6da3, #0d4e79);
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 50px;
                font-weight: 500;
                margin-top: 20px;
                text-align: center;
                box-shadow: 0 4px 10px rgba(0,0,0,0.12);
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
                .contact-info {
                    flex-direction: column;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="header-bg"></div>
                <div class="header-content">
                    <h1>' . $subject . '</h1>
                    <p class="header-date">' . date('d/m/Y H:i') . '</p>
                </div>
                <div class="logo-container">
                    <img src="https://yearim-club.co.il/תמונות/לוגו.png" alt="יערים קלאב" class="logo">
                </div>
            </div>
            
            <div class="content">
                <div class="intro">
                    <p><strong>שלום רב,</strong></p>
                    <p>';
                    
    // הוספת אייקון מתאים לפי סוג הטופס
    if ($form_type == "swimming") {
        $message_html .= '<i class="fas fa-swimming-pool"></i> ';
        $message_html .= 'התקבלה פנייה חדשה דרך טופס ההרשמה לבית הספר לשחייה באתר קאנטרי יערים קלאב.';
    } else {
        $message_html .= '<i class="fas fa-envelope-open-text"></i> ';
        $message_html .= 'התקבלה פנייה חדשה דרך טופס יצירת הקשר באתר קאנטרי יערים קלאב.';
    }
    
    $message_html .= '</p>
                </div>
                
                <h2 class="section-title">';
    
    // הוספת אייקון מתאים לכותרת
    if ($form_type == "swimming") {
        $message_html .= '<i class="fas fa-clipboard-list"></i> פרטי ההרשמה';
    } else {
        $message_html .= '<i class="fas fa-user-edit"></i> פרטי הפנייה';
    }
    
    $message_html .= '</h2>
                
                <div class="form-details">
                    <div class="form-title">';
                    
    // כותרת הפרטים לפי סוג הטופס
    if ($form_type == "swimming") {
        $message_html .= '<i class="fas fa-swimmer"></i> פרטי הרשמה לבית הספר לשחייה';
    } else {
        $message_html .= '<i class="fas fa-envelope"></i> פרטי הפנייה';
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
                <h3><strong class="highlight">קאנטרי יערים קלאב</strong></h3>
                
                <div class="social-links">
                    <a href="tel:025953535" title="חייגו אלינו"><i class="fas fa-phone-alt"></i></a>
                    <a href="https://wa.me/972504008038" title="וואטסאפ" class="whatsapp" style="background-color: #25d366;"><i class="fab fa-whatsapp"></i></a>
                    <a href="https://www.facebook.com/countryyearim/?locale=he_IL" title="דף הפייסבוק שלנו" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://mail.google.com/mail/u/0/?fs=1&tf=cm&source=mailto&su=פנייה%20לקאנטרי%20יערים%20קלאב&to=kantri360a@gmail.com&body=שלום%20רב,%0A%0Aאשמח%20לקבל%20מידע%20נוסף%20על%20" target="_blank" title="שלח אימייל"><i class="fas fa-envelope"></i></a>
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
    $message_text .= "טלפון: 02-5953535\n";
    $message_text .= "וואטסאפ: 050-4008038\n";
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
    
    // התאמה לשרת vangus - יש לכלול יותר כותרות עבור אבטחה
    $headers = "From: =?UTF-8?B?".base64_encode("קאנטרי יערים קלאב")."?= <" . $from . ">\r\n";
    
    // מניעת הזרקת כותרות במייל
    $safe_email = '';
    if (isset($fields['email'])) {
        // וידוא שאין ניסיון להזרקת כותרות
        $email = str_replace(["\r", "\n", "%0a", "%0d"], '', $fields['email']);
        // בדיקה נוספת שהאימייל תקין
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $safe_email = $email;
        } else {
            $safe_email = $from; // אם לא תקין, משתמשים באימייל ברירת מחדל
        }
    } else {
        $safe_email = $from;
    }
    
    $headers .= "Reply-To: " . $safe_email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"boundary\"\r\n";
    
    // הוספת כותרות אבטחה המתאימות לשרתי vangus
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "X-Sender-IP: " . $_SERVER['REMOTE_ADDR'] . "\r\n";
    $headers .= "X-Priority: 3\r\n";

    // שליחת המייל
    $mailSent = false;

    // ניסיון שליחה עם mail() הרגיל
    try {
        $mailSent = mail($to, $subject, $body, $headers);
    } catch (Exception $e) {
        error_log("שגיאה בשליחת מייל: " . $e->getMessage());
        $mailSent = false;
    }

    // אם נכשל, מנסים שוב עם פונקציה אלטרנטיבית (לשרתי vangus)
    if (!$mailSent) {
        try {
            // בדיקה אם קיימת פונקצית שליחת מיילים חלופית על השרת
            if (function_exists('custom_mail_send')) {
                $mailSent = custom_mail_send($to, $subject, $body, $headers);
            }
        } catch (Exception $e) {
            error_log("שגיאה בשליחת מייל (ניסיון שני): " . $e->getMessage());
            $mailSent = false;
        }
    }

    if ($mailSent) {
        echo json_encode(["status" => "success", "message" => "ההודעה נשלחה בהצלחה"]);
    } else {
        // שמירת לוג שגיאה
        error_log("שגיאה בשליחת מייל: " . date('Y-m-d H:i:s') . " - נשלח ל: " . $to . " - סוג טופס: " . $form_type, 0);
        echo json_encode(["status" => "error", "message" => "אירעה שגיאה בשליחת ההודעה, אנא צור קשר בטלפון."]);
    }
    exit;
}

// אם הגענו לכאן, הבקשה לא הייתה תקינה
echo json_encode(["status" => "error", "message" => "בקשה לא תקינה"]);
exit;

// הוספת לוג אבטחה
function log_security_event($event, $ip, $details = '') {
    $log_file = "security_log.txt";
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] IP: {$ip} - {$event}";
    if (!empty($details)) {
        $log_message .= " - Details: {$details}";
    }
    $log_message .= "\n";
    
    // בדיקה אם קובץ הלוג קיים וניתן לכתיבה
    if (file_exists($log_file) && is_writable($log_file)) {
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}

// הוספת תיעוד לניסיונות גישה חשודים
if (!$is_valid_referer && !empty($_SERVER['HTTP_REFERER'])) {
    log_security_event("Invalid referer access attempt", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_REFERER']);
}
?>
