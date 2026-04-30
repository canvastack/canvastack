/**
 * Debug Icon Rendering - Deep Analysis
 * 
 * Analyzes why Bootstrap Icons are not rendering properly
 * even after successful class conversion.
 */

(function() {
    'use strict';
    
    console.log('🔬 Icon Rendering Debug - Starting deep analysis...');
    
    function analyzeIconRendering() {
        console.log('🔬 ===== ICON RENDERING ANALYSIS =====');
        
        // 1. Check Bootstrap Icons font loading
        const testDiv = document.createElement('div');
        testDiv.style.fontFamily = 'bootstrap-icons';
        testDiv.style.position = 'absolute';
        testDiv.style.visibility = 'hidden';
        testDiv.innerHTML = '&#xF91A;'; // fork-knife unicode
        document.body.appendChild(testDiv);
        
        const computedFont = window.getComputedStyle(testDiv).fontFamily;
        console.log('🔬 Bootstrap Icons font test:', {
            element: testDiv,
            computedFont: computedFont,
            isLoaded: computedFont.includes('bootstrap-icons')
        });
        
        document.body.removeChild(testDiv);
        
        // 2. Analyze all Bootstrap Icons in sidebar
        const biIcons = document.querySelectorAll('.sidebar-nav .bi');
        console.log(`🔬 Found ${biIcons.length} Bootstrap Icons elements`);
        
        biIcons.forEach((icon, index) => {
            const computedStyle = window.getComputedStyle(icon, '::before');
            const computedStyleAfter = window.getComputedStyle(icon, '::after');
            
            console.log(`🔬 BI Icon ${index}:`, {
                element: icon,
                classes: Array.from(icon.classList),
                parentText: icon.closest('a') ? icon.closest('a').textContent.trim() : 'Unknown',
                
                // Computed styles
                fontFamily: computedStyle.fontFamily,
                content: computedStyle.content,
                display: computedStyle.display,
                fontSize: computedStyle.fontSize,
                color: computedStyle.color,
                
                // Check if ::before exists
                beforeContent: computedStyle.content,
                beforeDisplay: computedStyle.display,
                
                // Check if ::after exists  
                afterContent: computedStyleAfter.content,
                
                // Element properties
                offsetWidth: icon.offsetWidth,
                offsetHeight: icon.offsetHeight,
                clientWidth: icon.clientWidth,
                clientHeight: icon.clientHeight,
                
                // Check if element is visible
                isVisible: icon.offsetParent !== null,
                
                // Get actual rendered content
                innerHTML: icon.innerHTML,
                textContent: icon.textContent
            });
        });
        
        // 3. Test specific problematic icons
        console.log('🔬 ===== TESTING SPECIFIC ICONS =====');
        
        const testIcons = [
            { class: 'bi-fork-knife', unicode: '\\F91A', name: 'Fork Knife' },
            { class: 'bi-house', unicode: '\\F425', name: 'House' },
            { class: 'bi-gear', unicode: '\\F3E5', name: 'Gear' },
            { class: 'bi-grid-1x2', unicode: '\\F3F4', name: 'Grid 1x2' }
        ];
        
        testIcons.forEach(iconTest => {
            const testElement = document.createElement('i');
            testElement.className = `bi ${iconTest.class}`;
            testElement.style.fontSize = '20px';
            testElement.style.color = 'red';
            testElement.style.position = 'absolute';
            testElement.style.top = '10px';
            testElement.style.left = `${testIcons.indexOf(iconTest) * 30 + 10}px`;
            testElement.style.zIndex = '9999';
            
            document.body.appendChild(testElement);
            
            setTimeout(() => {
                const computedStyle = window.getComputedStyle(testElement, '::before');
                console.log(`🔬 Test ${iconTest.name}:`, {
                    class: iconTest.class,
                    expectedUnicode: iconTest.unicode,
                    actualContent: computedStyle.content,
                    fontFamily: computedStyle.fontFamily,
                    isRendering: computedStyle.content !== 'none' && computedStyle.content !== '""',
                    element: testElement
                });
                
                // Remove test element after 5 seconds
                setTimeout(() => {
                    if (testElement.parentNode) {
                        testElement.parentNode.removeChild(testElement);
                    }
                }, 5000);
            }, 100);
        });
        
        // 4. Check CSS loading order
        console.log('🔬 ===== CSS LOADING ORDER =====');
        const stylesheets = Array.from(document.styleSheets);
        stylesheets.forEach((sheet, index) => {
            try {
                console.log(`🔬 Stylesheet ${index}:`, {
                    href: sheet.href,
                    title: sheet.title,
                    disabled: sheet.disabled,
                    media: sheet.media.mediaText,
                    rulesCount: sheet.cssRules ? sheet.cssRules.length : 'Cannot access'
                });
            } catch (e) {
                console.log(`🔬 Stylesheet ${index}: Cannot access (CORS)`, sheet.href);
            }
        });
        
        // 5. Check for CSS conflicts
        console.log('🔬 ===== CSS CONFLICTS CHECK =====');
        const sampleIcon = document.querySelector('.sidebar-nav .bi');
        if (sampleIcon) {
            const allStyles = window.getComputedStyle(sampleIcon);
            console.log('🔬 Sample icon computed styles:', {
                fontFamily: allStyles.fontFamily,
                fontSize: allStyles.fontSize,
                fontWeight: allStyles.fontWeight,
                fontStyle: allStyles.fontStyle,
                textRendering: allStyles.textRendering,
                webkitFontSmoothing: allStyles.webkitFontSmoothing,
                mozOsxFontSmoothing: allStyles.mozOsxFontSmoothing
            });
        }
        
        console.log('🔬 ===== ANALYSIS COMPLETE =====');
    }
    
    // Run analysis after DOM is ready and fonts are loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(analyzeIconRendering, 2000); // Wait 2 seconds for fonts to load
        });
    } else {
        setTimeout(analyzeIconRendering, 2000);
    }
    
})();