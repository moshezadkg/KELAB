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
    $to = "h0527104792@gmail.com"; // כתובת האימייל אליה תישלח ההודעה
    $from = "yearim@yearim-club.co.il"; // כתובת השולח
    
    // נושא ההודעה לפי סוג הטופס
    if ($form_type == "swimming") {
        $subject = "הרשמה לבית הספר לשחייה - קאנטרי יערים קלאב";
    } else if ($form_type == "event") {
        $subject = "פנייה חדשה לאירוע - קאנטרי יערים קלאב";
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
    } else if ($form_type == "event") {
        $required_fields[] = 'eventType';
        $required_fields[] = 'guests';
        $required_fields[] = 'eventDate';
        $required_fields[] = 'eventTime';
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
    
    // בניית גוף ההודעה בפורמט פשוט ואמין יותר
    $to = "h0527104792@gmail.com"; // כתובת אימייל הנמען
    $from_name = "קאנטרי יערים קלאב";
    $from_email = "yearim@yearim-club.co.il"; // שינוי לכתובת אימייל חזקה יותר
    
    // כותרות המייל בפורמט בסיסי ואמין
    $headers = "";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?".base64_encode($from_name)."?= <".$from_email.">\r\n";
    $headers .= "Reply-To: ".$safe_email."\r\n";
    $headers .= "X-Mailer: PHP/".phpversion()."\r\n";
    
    // גרסה פשוטה של הודעת HTML
    $simple_html = '
    <!DOCTYPE html>
    <html dir="rtl">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>'.$subject.'</title>
    </head>
    <body style="direction: rtl; font-family: Arial, sans-serif; background-color: #f7f7f7; margin: 0; padding: 20px;">
        <div style="max-width: 650px; margin: 0 auto; background-color: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            <!-- כותרת עם לוגו -->
            <div style="background: linear-gradient(135deg, #0d4e79, #1c6da3); color: white; padding: 20px; display: flex; align-items: center;">
                <div style="display: flex; align-items: center; width: 100%;">
                    <div style="flex: 0 0 auto; margin-left: 15px;">
                        <img src="https://yearim-club.co.il/תמונות/לוגו.png" alt="קאנטרי יערים קלאב" style="width: 80px; height: 80px; border-radius: 50%; background-color: white; padding: 5px; box-shadow: 0 3px 10px rgba(0,0,0,0.2);">
                    </div>
                    <div style="flex: 1;">
                        <h1 style="margin: 0; font-size: 24px; text-shadow: 1px 1px 2px rgba(0,0,0,0.2);">'.$subject.'</h1>';
    
    // קביעת אזור זמן לישראל
    date_default_timezone_set('Asia/Jerusalem');
    $simple_html .= '
                        <p style="margin: 5px 0 0; font-size: 14px; opacity: 0.9;">'.date('d/m/Y H:i').'</p>
                    </div>
                </div>
            </div>
            
            <!-- תוכן -->
            <div style="padding: 20px;">
                <div style="background-color: #f1f8fe; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-right: 4px solid #1c6da3;">
                    <p style="margin: 0;"><strong>שלום רב,</strong></p>
                    <p style="margin: 10px 0 0;">';
    
    // טקסט לפי סוג הטופס            
    if ($form_type == "swimming") {
        $simple_html .= 'התקבלה פנייה חדשה דרך טופס ההרשמה לבית הספר לשחייה באתר קאנטרי יערים קלאב.';
    } else if ($form_type == "event") {
        $simple_html .= 'התקבלה פנייה חדשה דרך טופס הרישום לאירוע באתר קאנטרי יערים קלאב.';
    } else {
        $simple_html .= 'התקבלה פנייה חדשה דרך טופס יצירת הקשר באתר קאנטרי יערים קלאב.';
    }
    
    $simple_html .= '</p>
                </div>
                
                <h2 style="font-size: 18px; color: #1c6da3; padding-bottom: 10px; border-bottom: 2px solid #d4af37; margin-top: 0;">';
                
    // כותרת לפי סוג הטופס
    if ($form_type == "swimming") {
        $simple_html .= 'פרטי ההרשמה';
    } else if ($form_type == "event") {
        $simple_html .= 'פרטי האירוע';
    } else {
        $simple_html .= 'פרטי הפנייה';
    }
    
    $simple_html .= '</h2>
                
                <div style="background-color: #fff; border: 1px solid #eaeaea; border-radius: 8px; margin-bottom: 20px;">';
    
    // כותרת טבלת פרטים
    $simple_html .= '
                    <div style="background: linear-gradient(to left, #1c6da3, #0d4e79); color: white; padding: 15px; font-weight: bold; border-radius: 8px 8px 0 0;">';
    
    if ($form_type == "swimming") {
        $simple_html .= 'פרטי הרשמה לבית הספר לשחייה';
    } else if ($form_type == "event") {
        $simple_html .= 'פרטי בקשה לאירוע';
    } else {
        $simple_html .= 'פרטי הפנייה';
    }
    
    $simple_html .= '</div>';
    
    // פרטי הטופס
    $counter = 0;
    foreach ($fields as $key => $value) {
        $field_name = '';
            
        // המרת שמות השדות לעברית
        switch($key) {
            case 'fullName': $field_name = 'שם מלא'; break;
            case 'childName': $field_name = 'שם הילד/ה'; break;
            case 'childAge': $field_name = 'גיל הילד/ה'; break;
            case 'phone': $field_name = 'טלפון'; break;
            case 'email': $field_name = 'דואר אלקטרוני'; break;
            case 'preferredDay': $field_name = 'יום מועדף'; break;
            case 'preferredTime': $field_name = 'שעה מועדפת'; break;
            case 'swimmingLevel': $field_name = 'רמת שחייה'; break;
            case 'subject': $field_name = 'נושא הפנייה'; break;
            case 'message': $field_name = 'הודעה'; break;
            case 'newsletter': $field_name = 'רוצה לקבל עדכונים'; break;
            // שדות עבור טופס אירועים
            case 'eventType': $field_name = 'סוג האירוע'; break;
            case 'guests': $field_name = 'מספר אורחים'; break;
            case 'eventDate': $field_name = 'תאריך האירוע'; break;
            case 'eventTime': $field_name = 'שעות האירוע'; break;
            default: $field_name = $key; break;
        }
        
        $bg_color = $counter % 2 == 0 ? '#f9f9f9' : '#ffffff';
        
        $simple_html .= '
                    <div style="padding: 12px 15px; background-color: '.$bg_color.'; border-bottom: 1px solid #eaeaea;">
                        <strong style="color: #1c6da3; display: inline-block; width: 140px;">'.$field_name.':</strong>
                        <span>'.(in_array($key, array('fullName', 'childName', 'phone', 'email')) ? '<strong>'.nl2br($value).'</strong>' : nl2br($value)).'</span>
                    </div>';
        
        $counter++;
    }
    
    $simple_html .= '
                </div>
            </div>
            
            <!-- פוטר -->
            <div style="text-align: center; padding: 20px; background-color: #f7f7f7; border-top: 1px solid #eee;">
                <p style="margin: 0 0 10px; font-weight: bold; color: #d4af37;">קאנטרי יערים קלאב</p>
                <p style="margin: 0;">
                    <a href="tel:02-5953535" style="color: #1c6da3; text-decoration: none; margin: 0 5px;">
                        <span style="display: inline-flex; align-items: center; background-color: #edf6ff; border-radius: 30px; padding: 5px 10px; border: 1px solid #d0e8ff;">
                            <img src="https://yearim-club.co.il/תמונות/phone-icon.png" alt="טלפון" style="width: 16px; height: 16px; margin-left: 5px; background-color: #1c6da3; padding: 3px; border-radius: 50%;"> 02-5953535
                        </span>
                    </a>
                    <a href="https://wa.me/972504008038" style="color: #1c6da3; text-decoration: none; margin: 0 5px;">
                        <span style="display: inline-flex; align-items: center; background-color: #edf6ff; border-radius: 30px; padding: 5px 10px; border: 1px solid #d0e8ff;">
                            <img src="https://yearim-club.co.il/תמונות/whatsapp-icon.png" alt="וואטסאפ" style="width: 16px; height: 16px; margin-left: 5px; background-color: #25D366; padding: 3px; border-radius: 50%;"> 050-4008038
                        </span>
                    </a>
                </p>
                <p style="margin: 10px 0 0;">
                    <a href="mailto:kantri360a@gmail.com" style="color: #1c6da3; text-decoration: none; margin: 0 5px;">
                        <span style="display: inline-flex; align-items: center; background-color: #edf6ff; border-radius: 30px; padding: 5px 10px; border: 1px solid #d0e8ff;">
                            <img src="https://yearim-club.co.il/תמונות/email-icon.png" alt="אימייל" style="width: 16px; height: 16px; margin-left: 5px; background-color: #0078D4; padding: 3px; border-radius: 50%;"> kantri360a@gmail.com
                        </span>
                    </a>
                    <a href="https://www.facebook.com/yearimclub" style="color: #1c6da3; text-decoration: none; margin: 0 5px;">
                        <span style="display: inline-flex; align-items: center; background-color: #edf6ff; border-radius: 30px; padding: 5px 10px; border: 1px solid #d0e8ff;">
                            <img src="https://yearim-club.co.il/תמונות/facebook-icon.png" alt="פייסבוק" style="width: 16px; height: 16px; margin-left: 5px; background-color: #1877F2; padding: 3px; border-radius: 50%;"> פייסבוק
                        </span>
                    </a>
                </p>
                <p style="margin: 15px 0 0; font-size: 12px; color: #999;">© '.date('Y').' קאנטרי יערים קלאב. כל הזכויות שמורות.</p>
            </div>
        </div>
    </body>
    </html>';
    
    // גרסת טקסט פשוטה
    $simple_text = "פנייה חדשה מאתר קאנטרי יערים קלאב\n";
    $simple_text .= "=================================\n\n";
    
    foreach($fields as $key => $value) {
        $field_name = '';
        switch($key) {
            case 'fullName': $field_name = 'שם מלא'; break;
            case 'childName': $field_name = 'שם הילד/ה'; break;
            case 'childAge': $field_name = 'גיל הילד/ה'; break;
            case 'phone': $field_name = 'טלפון'; break;
            case 'email': $field_name = 'דואר אלקטרוני'; break;
            case 'preferredDay': $field_name = 'יום מועדף'; break;
            case 'preferredTime': $field_name = 'שעה מועדפת'; break;
            case 'swimmingLevel': $field_name = 'רמת שחייה'; break;
            case 'subject': $field_name = 'נושא הפנייה'; break;
            case 'message': $field_name = 'הודעה'; break;
            case 'newsletter': $field_name = 'רוצה לקבל עדכונים'; break;
            // שדות עבור טופס אירועים
            case 'eventType': $field_name = 'סוג האירוע'; break;
            case 'guests': $field_name = 'מספר אורחים'; break;
            case 'eventDate': $field_name = 'תאריך האירוע'; break;
            case 'eventTime': $field_name = 'שעות האירוע'; break;
            default: $field_name = $key; break;
        }
        $simple_text .= $field_name . ": " . $value . "\n";
    }
    
    $simple_text .= "\n=================================\n";
    $simple_text .= "קאנטרי יערים קלאב | טלפון: 02-5953535";
    
    // ניסיון ישיר לשלוח
    $mailSent = false;
    
    try {
        // מנסה דרך mail() רגיל
        error_log("ניסיון שליחת מייל בסיסי ל: " . $to);
        $mailSent = mail($to, $subject, $simple_html, $headers);
        
        if (!$mailSent) {
            // בדיקת הסיבה לכישלון
            $error = error_get_last();
            error_log("שגיאה בפונקציית mail(): " . ($error ? $error['message'] : 'לא ידוע'));
            
            // ניסיון אחרון עם פורמט פשוט יותר
            $basic_headers = "From: " . $from_email . "\r\n" .
                           "Reply-To: " . $safe_email . "\r\n" .
                           "Content-Type: text/plain; charset=UTF-8\r\n";
                           
            $mailSent = mail($to, $subject, $simple_text, $basic_headers);
            
            if ($mailSent) {
                error_log("הצלחה בשליחת מייל בפורמט פשוט");
            }
        }
    } catch (Exception $e) {
        error_log("Exception בשליחת מייל: " . $e->getMessage());
    }
    
    if ($mailSent) {
        error_log("המייל נשלח בהצלחה ל-" . $to);
        echo json_encode(["status" => "success", "message" => "ההודעה נשלחה בהצלחה"]);
    } else {
        // לוג מפורט של שגיאה
        $log_message = "שגיאה בשליחת מייל: " . date('Y-m-d H:i:s') . "\n";
        $log_message .= "נשלח ל: " . $to . "\n";
        $log_message .= "מאת: " . $from_email . "\n";
        $log_message .= "נושא: " . $subject . "\n";
        $log_message .= "סוג טופס: " . $form_type . "\n";
        $log_message .= "שגיאת PHP האחרונה: " . print_r(error_get_last(), true) . "\n";
        
        error_log($log_message, 3, "mail_errors.log");
        
        // מנסה לשמור את הנתונים בקובץ במקרה שהמייל נכשל
        $backup_file = "form_submissions.txt";
        $backup_content = "=== טופס חדש: " . date('Y-m-d H:i:s') . " ===\n";
        $backup_content .= "סוג: " . $form_type . "\n";
        $backup_content .= $simple_text . "\n\n";
        
        file_put_contents($backup_file, $backup_content, FILE_APPEND);
        
        echo json_encode([
            "status" => "success", // מחזיר הצלחה למרות הכישלון כדי שהמשתמש לא יראה שגיאה
            "message" => "הפנייה התקבלה בהצלחה. ניצור איתך קשר בהקדם.",
            "saved_locally" => true // מסמן שהפרטים נשמרו מקומית
        ]);
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

// הוספת אייקונים מוטמעים בבסיס 64
$base64_icons = array(
    // אייקון מעטפה - envelope-icon.png
    'envelope' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAACXBIWXMAAAsTAAALEwEAmpwYAAABWklEQVR4nO2Tv0vDQBTHv5dLVdC1k4ODuFgQHAQnewcHJ/+CDg7iIji0mQT/AAcHXRx0UXBxx/4FIojg4I8OFRwEcbFTwSKl5M4nNoQkTerPlQ+8uLzLfT73uwP+qzwADXq9AeAAwAGAJoA66V6AA3DtqqZJ+4GD7APYldLVAIxd0QbGOExKCojOF9rGnAIwT+kOrClb7Qg6jvyFLwAWcqITEykZY57QjsWgHMK2rUoYRjOUTliWtSqEOC4CnQN47XVbWOQEoAsATwDcQdCgVCpN9vv9CWPMKwIRrOFDhJcLNEUg3/c3+v3+DYk3LwMihJDbfr9fR872yoI6nU6bPNoAMAOgQsZDACsAVgF8ArhCTn3kGQZ9UNp2SXtD2GkURXMALgHsAXgmyDaALSnlPABfShlc2DGlPg2MscGyqCa8N6jktQsA2rTvCsBrEAQHRaBxfQMse/F3fQPIQPgFBpWpPgAAAABJRU5ErkJggg==',
    
    // אייקון שחייה - swimming-icon.png
    'swimming' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAACXBIWXMAAAsTAAALEwEAmpwYAAAB1klEQVR4nJWUv2sUQRTHP7N3e55iDAFBxUKxSKEgWKiFYJtCrGIjaGGhRcD/QbCwsBBBsMjBLYQUKWwEO0ErlQRyWIghwQTC5ZK9293ZmVnfz2wudyG58MEwy8z7zPu+eW/gP0t57aMGI8Bl4CxwAhgDqr7vOfAd2AV2gB/An+KilVtpDcaiqi9V9b2q7moQe6q6q6rvVPWVql5R1cZh8sFiS1UfquqP7EJHVbdUdVpV6/7bjqo+8nu/VPWB319L7zqWM38BtAbyy8CGN78OvAE+Ad9871zgNw9cTcq3LYkBJoE7flqnpaBn+6D58N8ZyrI0mUUmvYttHyNT94EzSYgWkPiR60lLVfU7OpubfcwXwLz//wgsAHVgHDgLnAauAfeAkbwUm4G7bRzHkzgxXZ5EKlEUj4ZhNJRlWc33dqL4qKqu+JCvAUcKYOvACvDMT/QUOHHYr9XwrSRv1FbVNY9J2q+TwG3guzf9BhwveikVj8lLb7rlgWsEweCCiCwVPE8BDeAWcB84ZIgBjg0MEzKqxSxNc0SDt6u5h/V8/VrAZ4ChIpj1wGZBxETEGJOJyEgYhpZiXOw5iwhJkuy1222TJEmWfXIwrD/a3W43S9O0Y4zZt9YWx/0XjWc9DZGk3OMAAAAASUVORK5CYII=',
    
    // אייקון רשימה - list-icon.png
    'list' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAACXBIWXMAAAsTAAALEwEAmpwYAAAA8ElEQVR4nO3TPy8EURTF8d8bSysUQvwpRSUKnUgUWpvobKdUiGgkCgqlQifrAxjvnYnKTqLTacUnkFAoFf6MxpItu8ZuJnbzVie5yT33nJNzb+A/VhO1VGRT9i6Nc9UR4j6WcY7baOMSMwVC+pjHCa6yPlfpfZTXdYETsbr4CBk5T/2uA6T7G9A6pvGAvjxIBx/yyTmmUiQdrGWQoWjeI6QqdnaTBckn20xdN3CFxfR9BXOiJ56wjHvcFm02xSEG8YtVMeLJLyHNNF57uMMQrvGKMJdsnxqilYbVfoPcpwK1IqBmAbIWMBswyZj+WJ8vL/lHxzulzQAAAABJRU5ErkJggg==',
    
    // אייקון משתמש - user-icon.png
    'user' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAACXBIWXMAAAsTAAALEwEAmpwYAAABT0lEQVR4nO2SP0gCYRjGf9coNEThEARBQUs0OTY11tLUEG2NNNTQ0BotQktTYzS1NkRDNDVF0CAE0R8IgpYIAqEpiIaghrCiuucrj5J7vTO3lR54eHnf7/me572HZ2BBabrOCrCuQQOoAq+AKeqSkBpgAQfa9zoQFP2nhlKgF9gHXCAP+BVLwB5QA1KB5JTJvA+k2W63i61Wq6Tr+k6n09kDDoEx4AE4B1JjdWiQZgVYBS5EeVqGYcTL5bJXFEXJsqxZWZYfZVmek2X5RVGU+Wq1OquqakLXdQNYBp5HgXZEN4bP58uUSqUdQRDAtu1BpdP9wqiq6tA0LWbbdlLCpoE34GbEC3VhRyKRvfF4PCWOY8myLGHbdm4ymWQnguiA18Cd+L5TIwOuJUzCpoAQEAQWAWMmyCXwAzxPCr8BPoHvWSFnwAXwERDcDRz+AkkDzBLwSRNvAAAAAElFTkSuQmCC',
    
    // אייקון שחיין - swimmer-icon.png
    'swimmer' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAACXBIWXMAAAsTAAALEwEAmpwYAAABuElEQVR4nK2UO2tUURSFv3PvncmDRMGoSBARJdhYaWVjnS5NQBsrlf9grY2VhY1gIZZ2NjYBGwsbbQIBGy0UfEXNZObO3WutPZbjDHeS4IM94HAO56y9H/sI/mnycmMdGAG7wCHQJ+kDd2LsALuqekxVfwI4cBNYBUqgcPecpKKqqgPg9Dmc84eqeiYzz1TVHyYaAhvAMjAHzHp7UTQD9FJKw5zzIOc8SClNAZNSyhB4A3xYKMu5ObNRGo/rLrANvAJuAWcXbM8DV4CNJeBS/P8KbAOXowsO3ATmgeXI7DFwLTbvBHAF+ALsN+4dD6DTkdkB8DlyLIA+8CH2vw/Q/QB9GivNOmsBbqvqDRGZBd6JyMekWpKQWymlgbtv5JxvR/cOrHX3m6r6HBgBPXfvRxkvzX07GEQ8L4HzwOu47wLwHDgn5rZ4eTxOd4BbwC3gQWiUA3QpiFMO3I9OVsAj4LG7b0VWF4GbwK4A74DnEUQPeGlmoxhFD/gY4ayE2Ksi+oFrzQIfgfdqbk+i7X13X3bfrtx9lXNejcm6G2K9CYy/RUcVEdeEiNTAf68/PVkxP5EKPTUAAAAASUVORK5CYII=',
    
    // אייקון טלפון - phone-icon.png
    'phone' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAACXBIWXMAAAsTAAALEwEAmpwYAAAA/0lEQVR4nO3TPUtDQRAF0BOjIvrXxNLKQhBsfBL8AfkBYuEPsbCxtrGxsdHSyt5CwcLKwsIn2AgKFmKhnZWlgjFuMCtLsNkX+yQXFmZn9t47M7OEo1APfcEvzRMK6qCrqIU/wDnc4jn2LtDGZUzEfk4XlQEVscR+vLk4GMY13uIwuU1UkuSUcR7DMRxiKo7JZMWKWMUoDjCNt/B6eMAy9tJms9iJ82hgVoUdXMcZtuLdI3ykmGTBbmQwHoZkTvAZi8msFbAW58zFpb6CfcVF00A1toPZxwZ6eMFGJJhAOStRGoYylnCJZlbsv1TO8imv4wUPuMcBZv4S0P9yZdQBIg2yXY0hX/IAAAAASUVORK5CYII=',
    
    // אייקון וואטסאפ - whatsapp-icon.png
    'whatsapp' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAACXBIWXMAAAsTAAALEwEAmpwYAAABhUlEQVR4nO2TPUgcURSFz5llQrILrsofqIWgYLtgaxciFlYGS8U/sLMIEbS0ChYW2loFO4uQVsRCsbKwF0Kw0UI22YDZndnZ8XvP2Vmd0ZnNImjhgcvjzn33nHvfvIH/Dk3+ySB8GmRYV9BtJxHWaOXu4bWA36CgkUY8RL9mwSAAKe9fkbQKsqrSjLt9gswBaJKJMw2jvmUU9EAl7pQAHCiQTzNQUk0dCPQZ4SaRBiT1Wq2w4zia0XUI9BSrQSvJDDLMLa+wfIhN55xZluedc87OOffOOTfvnJvz3r/NsmzOGDP1N9jnLKu8MMbMZVm2mOf5vHNu0Vq7bK1dM8aseO8XiOgFEU0R0XciGiei1wCeA3imSFw6xqrGUOvNRtJttVm8yIvFYiuO45/W2tftFm3X646cNgwR0b5SCgAwVjLakTQA1G00GoP1en2oXq8PVqvV0Uql8iCKonuVSmWgWq0ORlE0Wq1Wh4joTnd3d39vb++9RCLRl0wmh5PJ5L1EItGXSqV6UqnUbSKKAHwBtHx6E2m9GlsAAAAASUVORK5CYII=',
    
    // אייקון פייסבוק - facebook-icon.png
    'facebook' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAACXBIWXMAAAsTAAALEwEAmpwYAAAA/UlEQVR4nO3TPyjFURgG8I/8uVwMBoOUgZQMFoPdYmOzGJSSxZ9kYZXJZLEwWCgGIwabUjKcUkpK7l0shivJIJfkXufrfKe+7h0YeOp06ume9zzP+z5wWhmGMMK/YxDL2McWZtCN3Wjc+A1kGifIlvlzlJ+wjFFM4jRi52jLBzKATWToi+F+HONF5sOIZU+Fomz9jfcm1tCKZQw3AhkoDLiMdXQUNXSiBydowa6C6iQ94PQbkFzuMAAgGU0yjgGsRH1eE7CL3mR8SyUVbMWOCvmuv+iZxTMeYgfl7CLI5OJ+sRCQL7zhJtYVtbmGJpBJXOINt6UgjfiSi99KVbkH/K++ACTkf4sE5WhWAAAAAElFTkSuQmCC',
    
    // אייקון אימייל - email-icon.png
    'email' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAACXBIWXMAAAsTAAALEwEAmpwYAAAA7klEQVR4nO2TPUoDURSFv8n8EBBsRBArO8HCtSwibmAt6Q1ZQlyCaxBcgK2dpZWQwlZQm9GZl3POIBNMxskQK/VrLu9x7z3vvQP/MTb5V6DjUdAQZ4IfQQKqmM8nqAQSyBJXpUcOBU4CelJYeANxhtgEZsjzhCLCVUk0RkHmEJWvIA3QAulCSQ96rlwAl1Loe2OFuhT6jUYYO1jDGGMMnTGMlcLSe8SsS7EriS6dDdZAkO/QKXANnDhIcaytgIZoKq+AF+B+n8obYIgyB3aSZG08YY1n7JVt8eO4B8xNYVyuutkn5A2YqvJFcT2X/7h9AKlXfEXvhTvfAAAAAElFTkSuQmCC'
);

// בחירת עיצוב אייקונים - בסיס 64 במקום קבצים
function get_icon($icon_type) {
    global $base64_icons;
    return $base64_icons[$icon_type];
}
?>
