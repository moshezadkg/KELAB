/**
 * קובץ נגישות לאתר קאנטרי יערים קלאב
 * מאפשר שינוי גודל טקסט, ניגודיות, ופונקציות נגישות נוספות
 */

class AccessibilityManager {
    constructor() {
        this.fontSizeLevel = 0;
        this.highContrastMode = false;
        this.linksHighlighted = false;
        this.menuOpen = false;
        
        this.init();
    }
    
    init() {
        // יצירת כפתור הנגישות
        this.createAccessibilityButton();
        
        // טעינת הגדרות נגישות מהאחסון המקומי, אם קיים
        this.loadSettings();
        
        // החלת ההגדרות שנטענו
        this.applySettings();
    }
    
    createAccessibilityButton() {
        // יצירת כפתור הנגישות הצף
        const accessibilityBtn = document.createElement('div');
        accessibilityBtn.className = 'accessibility-btn';
        accessibilityBtn.innerHTML = '<i class="fas fa-universal-access"></i>';
        accessibilityBtn.title = 'אפשרויות נגישות';
        accessibilityBtn.setAttribute('aria-label', 'פתח תפריט נגישות');
        accessibilityBtn.setAttribute('role', 'button');
        accessibilityBtn.setAttribute('tabindex', '0');

        // הוספת הכפתור לגוף האתר
        const accessibilityContainer = document.createElement('div');
        accessibilityContainer.className = 'accessibility-container';
        accessibilityContainer.appendChild(accessibilityBtn);
        document.body.appendChild(accessibilityContainer);

        // יצירת מודאל הנגישות (סגור כברירת מחדל)
        const modal = document.createElement('div');
        modal.className = 'accessibility-modal';
        modal.style.display = 'none';
        modal.style.position = 'fixed';
        modal.style.bottom = '90px';
        modal.style.left = '30px';
        modal.style.zIndex = '10000';
        modal.style.background = 'none';
        modal.style.width = 'auto';
        modal.style.height = 'auto';
        modal.style.alignItems = 'flex-end';
        modal.style.justifyContent = 'flex-start';
        modal.innerHTML = `
            <div class="accessibility-modal-content" style="background: #fff; border-radius: 18px; width: 320px; max-width: 90vw; box-shadow: 0 8px 32px rgba(28,109,163,0.18); padding: 0; overflow: hidden; position: relative; font-family: inherit;">
                <div style="background: #1c6da3; color: #fff; padding: 14px 18px 10px 18px; display: flex; align-items: center; justify-content: space-between; border-top-right-radius: 18px; border-top-left-radius: 18px;">
                    <span style="font-size: 1.1em; font-weight: bold; letter-spacing: 1px;">נגישות</span>
                    <button class="close-modal-btn" aria-label="סגור" style="background: none; border: none; color: #fff; font-size: 1.3em; cursor: pointer;">&times;</button>
                </div>
                <div style="padding: 16px 12px 8px 12px; display: flex; flex-direction: column; gap: 8px;">
                    <button id="increase-font" class="accessibility-action-btn" style="display: flex; flex-direction: column; align-items: center; background: #f5f8fa; border: none; border-radius: 10px; padding: 10px 0; cursor: pointer; margin-bottom: 2px; transition: background 0.2s;">
                        <i class="fas fa-font" style="font-size: 1.5em; color: #1c6da3;"></i>
                        <span style="font-size: 0.95em; color: #1c6da3; margin-top: 4px;">הגדל טקסט</span>
                    </button>
                    <button id="decrease-font" class="accessibility-action-btn" style="display: flex; flex-direction: column; align-items: center; background: #f5f8fa; border: none; border-radius: 10px; padding: 10px 0; cursor: pointer; margin-bottom: 2px; transition: background 0.2s;">
                        <i class="fas fa-font fa-xs" style="font-size: 1.1em; color: #1c6da3;"></i>
                        <span style="font-size: 0.95em; color: #1c6da3; margin-top: 4px;">הקטן טקסט</span>
                    </button>
                    <button id="high-contrast" class="accessibility-action-btn" style="display: flex; flex-direction: column; align-items: center; background: #f5f8fa; border: none; border-radius: 10px; padding: 10px 0; cursor: pointer; margin-bottom: 2px; transition: background 0.2s;">
                        <i class="fas fa-adjust" style="font-size: 1.5em; color: #1c6da3;"></i>
                        <span style="font-size: 0.95em; color: #1c6da3; margin-top: 4px;">ניגודיות גבוהה</span>
                    </button>
                    <button id="highlight-links" class="accessibility-action-btn" style="display: flex; flex-direction: column; align-items: center; background: #f5f8fa; border: none; border-radius: 10px; padding: 10px 0; cursor: pointer; margin-bottom: 2px; transition: background 0.2s;">
                        <i class="fas fa-link" style="font-size: 1.5em; color: #1c6da3;"></i>
                        <span style="font-size: 0.95em; color: #1c6da3; margin-top: 4px;">הדגש קישורים</span>
                    </button>
                    <button id="readable-font" class="accessibility-action-btn" style="display: flex; flex-direction: column; align-items: center; background: #f5f8fa; border: none; border-radius: 10px; padding: 10px 0; cursor: pointer; margin-bottom: 2px; transition: background 0.2s;">
                        <i class="fas fa-book-reader" style="font-size: 1.5em; color: #1c6da3;"></i>
                        <span style="font-size: 0.95em; color: #1c6da3; margin-top: 4px;">פונט קריא</span>
                    </button>
                    <button id="reset-accessibility" class="accessibility-action-btn" style="display: flex; flex-direction: column; align-items: center; background: #f5f8fa; border: none; border-radius: 10px; padding: 10px 0; cursor: pointer; margin-bottom: 2px; transition: background 0.2s;">
                        <i class="fas fa-undo" style="font-size: 1.5em; color: #1c6da3;"></i>
                        <span style="font-size: 0.95em; color: #1c6da3; margin-top: 4px;">איפוס</span>
                    </button>
                    <a href="#" id="accessibility-statement-link" style="display: block; text-align: center; color: #1c6da3; font-weight: bold; margin-top: 6px; text-decoration: underline; font-size: 0.95em;">הצהרת נגישות</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // פתיחת המודאל בלחיצה על הכפתור
        accessibilityBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
        });
        accessibilityBtn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                modal.style.display = 'flex';
            }
        });
        // סגירה ב-X
        modal.querySelector('.close-modal-btn').addEventListener('click', () => {
            modal.style.display = 'none';
        });
        // סגירה בלחיצה מחוץ לחלון
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        // סגירה ב-ESC
        document.addEventListener('keydown', function escListener(e) {
            if (modal.style.display === 'flex' && e.key === 'Escape') {
                modal.style.display = 'none';
            }
        });
        // מאזיני אירועים לאפשרויות בתפריט
        document.getElementById('increase-font').addEventListener('click', () => this.changeFontSize(1));
        document.getElementById('decrease-font').addEventListener('click', () => this.changeFontSize(-1));
        document.getElementById('high-contrast').addEventListener('click', () => this.toggleHighContrast());
        document.getElementById('highlight-links').addEventListener('click', () => this.toggleHighlightLinks());
        document.getElementById('readable-font').addEventListener('click', () => this.toggleReadableFont());
        document.getElementById('reset-accessibility').addEventListener('click', () => this.resetSettings());
        document.getElementById('accessibility-statement-link').addEventListener('click', (e) => {
            e.preventDefault();
            this.showAccessibilityStatement();
        });
    }
    
    changeFontSize(direction) {
        // הגבלת רמת הגדלה/הקטנה בין -3 ל-3
        const newLevel = Math.max(-3, Math.min(3, this.fontSizeLevel + direction));
        
        if (newLevel !== this.fontSizeLevel) {
            this.fontSizeLevel = newLevel;
            
            // הסרת כל מחלקות גודל הטקסט הקודמות
            document.body.classList.remove('font-size--3', 'font-size--2', 'font-size--1', 
                                           'font-size-0', 
                                           'font-size-1', 'font-size-2', 'font-size-3');
            
            // הוספת המחלקה המתאימה לגודל החדש
            document.body.classList.add(`font-size-${this.fontSizeLevel}`);
            
            this.saveSettings();
        }
    }
    
    toggleHighContrast() {
        this.highContrastMode = !this.highContrastMode;
        document.body.classList.toggle('high-contrast', this.highContrastMode);
        this.saveSettings();
    }
    
    toggleHighlightLinks() {
        this.linksHighlighted = !this.linksHighlighted;
        document.body.classList.toggle('highlight-links', this.linksHighlighted);
        this.saveSettings();
    }
    
    toggleReadableFont() {
        document.body.classList.toggle('readable-font');
        this.readableFontEnabled = document.body.classList.contains('readable-font');
        this.saveSettings();
    }
    
    saveSettings() {
        const settings = {
            fontSizeLevel: this.fontSizeLevel,
            highContrastMode: this.highContrastMode,
            linksHighlighted: this.linksHighlighted,
            readableFontEnabled: this.readableFontEnabled
        };
        
        localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
    }
    
    loadSettings() {
        try {
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings'));
            
            if (settings) {
                this.fontSizeLevel = settings.fontSizeLevel || 0;
                this.highContrastMode = settings.highContrastMode || false;
                this.linksHighlighted = settings.linksHighlighted || false;
                this.readableFontEnabled = settings.readableFontEnabled || false;
            }
        } catch (e) {
            console.error('Error loading accessibility settings', e);
            this.resetSettings(false);
        }
    }
    
    applySettings() {
        // החלת גודל טקסט
        if (this.fontSizeLevel !== 0) {
            document.body.classList.add(`font-size-${this.fontSizeLevel}`);
        }
        
        // החלת מצב ניגודיות גבוהה
        if (this.highContrastMode) {
            document.body.classList.add('high-contrast');
        }
        
        // החלת הדגשת קישורים
        if (this.linksHighlighted) {
            document.body.classList.add('highlight-links');
        }
        
        // החלת פונט קריא
        if (this.readableFontEnabled) {
            document.body.classList.add('readable-font');
        }
    }
    
    resetSettings(save = true) {
        // איפוס המשתנים
        this.fontSizeLevel = 0;
        this.highContrastMode = false;
        this.linksHighlighted = false;
        this.readableFontEnabled = false;
        
        // הסרת כל מחלקות הנגישות
        document.body.classList.remove(
            'font-size--3', 'font-size--2', 'font-size--1', 
            'font-size-0', 
            'font-size-1', 'font-size-2', 'font-size-3',
            'high-contrast', 'highlight-links', 'readable-font'
        );
        
        if (save) {
            this.saveSettings();
        }
    }
    
    showAccessibilityStatement() {
        // יצירת החלון המודאלי
        const modal = document.createElement('div');
        modal.className = 'accessibility-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-labelledby', 'accessibility-statement-title');
        
        modal.innerHTML = `
            <div class="accessibility-modal-content" style="background-color: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 50px auto; box-shadow: 0 4px 8px rgba(0,0,0,0.2); max-height: 80vh; overflow-y: auto;">
                <h2 id="accessibility-statement-title" style="color: #1c6da3; border-bottom: 2px solid #1c6da3; padding-bottom: 10px; margin-top: 0; font-size: 1.5em;">הצהרת נגישות - קאנטרי יערים קלאב</h2>
                
                <p style="font-size: 1.1em; line-height: 1.6;">אנו ביערים קלאב – קאנטרי מעלה החמישה מאמינים בשוויון זכויות לכל אדם באשר הוא ולכן אנו עושים כל שביכולתנו להנגיש את אתר האינטרנט והמיקום הפיזי.</p>
                
                <h3 style="color: #1c6da3; font-size: 1.3em;">סרגל הנגישות באתר:</h3>
                <p style="font-size: 1.1em; line-height: 1.6;">האתר מצוייד בסרגל נגישות על מנת לאפשר התאמה לבעלי מוגבלויות:</p>
                <ul style="list-style-type: disc; padding-right: 20px; font-size: 1.1em; line-height: 1.6;">
                    <li>הגדלת טקסט</li>
                    <li>הקטנת טקסט</li>
                    <li>גווני אפור</li>
                    <li>ניגודיות גבוהה</li>
                    <li>ניגודיות הפוכה</li>
                    <li>רקע בהיר</li>
                    <li>הדגשת קישורים</li>
                    <li>פונט קריא</li>
                    <li>איפוס</li>
                </ul>
                
                <h3 style="color: #1c6da3; font-size: 1.3em;">אמצעי קשר נוספים:</h3>
                <p style="font-size: 1.1em; line-height: 1.6;">
                    כמו כן קיים כפתור יצירת קשר בוואטס אפ בצד הימני התחתון של האתר.<br>
                    אנו נשמח כי תצרו איתנו קשר בכתב או טלפונית לקבלת שירות ועזרה.
                </p>
                
                <p style="font-size: 1.1em; line-height: 1.6;">במידה ונתקלתם בדבר מה אשר לא מונגש, נשמח לשמוע על כך ולתקן מיידית.<br>
                ניתן לפנות לרכזת הנגישות של החברה – אייל במספר: 02-5953535</p>
                
                <h3 style="color: #1c6da3; font-size: 1.3em;">נגישות פיזית בקאנטרי:</h3>
                <ul style="list-style-type: disc; padding-right: 20px; font-size: 1.1em; line-height: 1.6;">
                    <li>מגרש חניה בשטחי המלון המאפשר הגעה עם כסא גלגלים כולל מקומות חניה לנכים.</li>
                    <li>הקאנטרי מונגש לכסא גלגלים מהחניה לפתח המלון וכן קיימת מעלית המאפשרת הגעה נוחה לקאנטרי.</li>
                    <li>מעברים רחבים המאפשרים תנועה נוחה.</li>
                    <li>עמדות שירות בשולחנות רגילים לקבלת שירות ברמה גבוהה לכלל האוכלוסיה.</li>
                    <li>שירותי נכים – קיימים.</li>
                    <li>לולאת השראה – אין.</li>
                </ul>
                
                <p style="font-size: 1.1em; line-height: 1.6;">כמו כן, ניתן לתאם עימנו טלפונית לאדם עם מוגבלות ואנו נעשה כל שביכולתנו להתאים את הצרכים על מנת שיקבל את השירות הגבוה עליו אמונה חברתנו.</p>

                <h3 style="color: #1c6da3; font-size: 1.3em;">שעות פעילות הקאנטרי:</h3>
                <p style="font-size: 1.1em; line-height: 1.6;">
                    ימים א' + ג' – 05:30-21:40<br>
                    ימים ב' + ד' – 05:30-20:40<br>
                    *נשים בלבד 20:45-23:00*<br>
                    יום ה' – 05:30-20:30<br>
                    *גברים בלבד 20:30-23:00*<br>
                    יום שישי – 05:30-15:30<br>
                    יום שבת – 07:30-16:00<br>
                    מוצ"ש – 20:30-23:00 (גברים בלבד)
                </p>
                
                <h3 style="color: #1c6da3; font-size: 1.3em;">יצירת קשר:</h3>
                <p style="font-size: 1.1em; line-height: 1.6;">
                    יערים קלאב – קאנטרי מעלה החמישה<br>
                    מלון יערים, מעלה החמישה<br>
                    טלפון: <a href="tel:025953535" style="color: #1c6da3; text-decoration: underline;">02-5953535</a><br>
                    פקס: 02-6767720<br>
                    דוא"ל: <a href="mailto:kantri360a@gmail.com" style="color: #1c6da3; text-decoration: underline;">kantri360a@gmail.com</a>
                </p>
                
                <button class="close-modal-btn" style="background-color: #1c6da3; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-top: 20px; font-size: 1.1em;" aria-label="סגור חלון">סגור</button>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // מאזין לכפתור הסגירה
        modal.querySelector('.close-modal-btn').addEventListener('click', () => {
            document.body.removeChild(modal);
        });
        
        // סגירה בלחיצה מחוץ לתוכן
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
        
        // סגירה בלחיצה על ESC
        document.addEventListener('keydown', function escListener(e) {
            if (e.key === 'Escape') {
                document.body.removeChild(modal);
                document.removeEventListener('keydown', escListener);
            }
        });
    }
}

// יצירת האובייקט כשהדף נטען
document.addEventListener('DOMContentLoaded', () => {
    new AccessibilityManager();
}); 