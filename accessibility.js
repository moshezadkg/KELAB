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
        // יצירת הכפתור הראשי
        const accessibilityBtn = document.createElement('div');
        accessibilityBtn.className = 'accessibility-btn';
        accessibilityBtn.innerHTML = '<i class="fas fa-universal-access"></i>';
        accessibilityBtn.title = 'אפשרויות נגישות';
        accessibilityBtn.setAttribute('aria-label', 'פתח תפריט נגישות');
        accessibilityBtn.setAttribute('role', 'button');
        accessibilityBtn.setAttribute('tabindex', '0');
        
        // יצירת תפריט הנגישות
        const accessibilityMenu = document.createElement('div');
        accessibilityMenu.className = 'accessibility-menu';
        accessibilityMenu.setAttribute('aria-hidden', 'true');
        
        // תוכן התפריט
        accessibilityMenu.innerHTML = `
            <div class="accessibility-title">אפשרויות נגישות</div>
            <button class="accessibility-option" id="increase-font" aria-label="הגדל טקסט">
                <i class="fas fa-font"></i><span>הגדל טקסט</span>
            </button>
            <button class="accessibility-option" id="decrease-font" aria-label="הקטן טקסט">
                <i class="fas fa-font fa-xs"></i><span>הקטן טקסט</span>
            </button>
            <button class="accessibility-option" id="high-contrast" aria-label="ניגודיות גבוהה">
                <i class="fas fa-adjust"></i><span>ניגודיות גבוהה</span>
            </button>
            <button class="accessibility-option" id="highlight-links" aria-label="הדגש קישורים">
                <i class="fas fa-link"></i><span>הדגש קישורים</span>
            </button>
            <button class="accessibility-option" id="readable-font" aria-label="פונט קריא">
                <i class="fas fa-book-reader"></i><span>פונט קריא</span>
            </button>
            <button class="accessibility-option" id="reset-accessibility" aria-label="איפוס הגדרות">
                <i class="fas fa-undo"></i><span>איפוס הגדרות</span>
            </button>
            <div class="accessibility-statement">
                <a href="#" id="accessibility-statement-link">הצהרת נגישות</a>
            </div>
        `;
        
        // הוספת הכפתור והתפריט לגוף האתר
        const accessibilityContainer = document.createElement('div');
        accessibilityContainer.className = 'accessibility-container';
        accessibilityContainer.appendChild(accessibilityBtn);
        accessibilityContainer.appendChild(accessibilityMenu);
        document.body.appendChild(accessibilityContainer);
        
        // הוספת מאזיני אירועים
        this.addEventListeners(accessibilityBtn, accessibilityMenu);
    }
    
    addEventListeners(accessibilityBtn, accessibilityMenu) {
        // פתיחה וסגירה של תפריט הנגישות
        accessibilityBtn.addEventListener('click', () => {
            this.toggleMenu(accessibilityMenu);
        });
        
        accessibilityBtn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggleMenu(accessibilityMenu);
            }
        });
        
        // מאזיני אירועים לאפשרויות בתפריט
        document.getElementById('increase-font').addEventListener('click', () => this.changeFontSize(1));
        document.getElementById('decrease-font').addEventListener('click', () => this.changeFontSize(-1));
        document.getElementById('high-contrast').addEventListener('click', () => this.toggleHighContrast());
        document.getElementById('highlight-links').addEventListener('click', () => this.toggleHighlightLinks());
        document.getElementById('readable-font').addEventListener('click', () => this.toggleReadableFont());
        document.getElementById('reset-accessibility').addEventListener('click', () => this.resetSettings());
        
        // סגירת התפריט בלחיצה מחוץ לתפריט
        document.addEventListener('click', (e) => {
            if (this.menuOpen && 
                !accessibilityBtn.contains(e.target) && 
                !accessibilityMenu.contains(e.target)) {
                this.toggleMenu(accessibilityMenu, false);
            }
        });
        
        // קישור להצהרת נגישות
        document.getElementById('accessibility-statement-link').addEventListener('click', (e) => {
            e.preventDefault();
            this.showAccessibilityStatement();
        });
    }
    
    toggleMenu(menu, forceState = null) {
        const newState = forceState !== null ? forceState : !this.menuOpen;
        this.menuOpen = newState;
        
        menu.style.display = newState ? 'block' : 'none';
        menu.setAttribute('aria-hidden', (!newState).toString());
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
            <div class="accessibility-modal-content">
                <h2 id="accessibility-statement-title">הצהרת נגישות - קאנטרי יערים קלאב</h2>
                <p>אתר זה מיועד לשימוש עבור כל אדם, כולל אנשים עם מוגבלויות. אנו עושים מאמץ מתמיד לוודא שהאתר עומד בדרישות תקנות שוויון זכויות לאנשים עם מוגבלות (התאמות נגישות לשירות), תשע"ג-2013.</p>
                
                <h3>אמצעי הנגישות באתר:</h3>
                <ul>
                    <li>התאמת גודל הטקסט לצרכי המשתמש</li>
                    <li>אפשרות למצב ניגודיות גבוהה</li>
                    <li>הדגשת קישורים באתר</li>
                    <li>שימוש בפונט קריא</li>
                    <li>תמיכה מלאה בניווט באמצעות מקלדת</li>
                    <li>תגיות תיאוריות לתמונות (alt text)</li>
                    <li>מבנה דף ברור וקל לניווט</li>
                </ul>
                
                <h3>יצירת קשר בנושא נגישות:</h3>
                <p>אם נתקלתם בבעיית נגישות באתר, אנא צרו איתנו קשר:</p>
                <p>רכז הנגישות: ישראל ישראלי<br>
                טלפון: 02-5953535<br>
                דוא"ל: accessibility@yearim-club.co.il</p>
                
                <button class="close-modal-btn" aria-label="סגור חלון">סגור</button>
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