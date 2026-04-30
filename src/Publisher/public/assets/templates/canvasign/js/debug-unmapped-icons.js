/**
 * Debug Unmapped Icons
 * 
 * Temporary script to identify Font Awesome icons that are not mapped
 * to Bootstrap Icons in the CSS file.
 */

(function() {
    'use strict';
    
    console.log('🔍 Debug Unmapped Icons - Starting analysis...');
    
    // Wait for DOM to be ready
    setTimeout(function() {
        
        // Find all Font Awesome icons in sidebar
        const faIcons = document.querySelectorAll('.sidebar-nav .fa[class*="fa-"]');
        
        console.log(`📋 Found ${faIcons.length} Font Awesome icons in sidebar`);
        
        const unmappedIcons = [];
        const mappedIcons = [];
        
        faIcons.forEach((icon, index) => {
            const classes = Array.from(icon.classList);
            const faClasses = classes.filter(cls => cls.startsWith('fa-'));
            
            // Get computed style to check if Bootstrap Icons font is applied
            const computedStyle = window.getComputedStyle(icon, '::before');
            const fontFamily = computedStyle.fontFamily;
            const content = computedStyle.content;
            
            const iconInfo = {
                element: icon,
                index: index,
                allClasses: classes.join(' '),
                faClasses: faClasses,
                fontFamily: fontFamily,
                content: content,
                parentText: icon.closest('a') ? icon.closest('a').textContent.trim() : 'Unknown'
            };
            
            // Check if Bootstrap Icons font is applied and content is not fallback
            if (fontFamily.includes('bootstrap-icons') && content !== '"\\F287"' && content !== 'none' && content !== '""') {
                mappedIcons.push(iconInfo);
                console.log(`✅ MAPPED: ${iconInfo.parentText} - ${iconInfo.faClasses.join(' ')} → ${content}`);
            } else {
                unmappedIcons.push(iconInfo);
                console.warn(`❌ UNMAPPED: ${iconInfo.parentText} - ${iconInfo.faClasses.join(' ')} (font: ${fontFamily}, content: ${content})`);
            }
        });
        
        console.log(`\n📊 SUMMARY:`);
        console.log(`✅ Mapped icons: ${mappedIcons.length}`);
        console.log(`❌ Unmapped icons: ${unmappedIcons.length}`);
        
        if (unmappedIcons.length > 0) {
            console.group('🚨 UNMAPPED ICONS - Need CSS mapping:');
            unmappedIcons.forEach(icon => {
                console.log(`Menu: "${icon.parentText}" | FA Classes: ${icon.faClasses.join(' ')} | All Classes: ${icon.allClasses}`);
            });
            console.groupEnd();
            
            console.group('📝 CSS MAPPINGS TO ADD:');
            const uniqueFaClasses = [...new Set(unmappedIcons.flatMap(icon => icon.faClasses))];
            uniqueFaClasses.forEach(faClass => {
                console.log(`.sidebar-nav .fa.${faClass}::before { content: '\\FXXX'; } /* bi-[BOOTSTRAP_ICON] */`);
            });
            console.groupEnd();
        }
        
        // Also check if Bootstrap Icons font is loaded
        const testElement = document.createElement('div');
        testElement.style.fontFamily = 'bootstrap-icons';
        testElement.style.position = 'absolute';
        testElement.style.visibility = 'hidden';
        testElement.textContent = 'test';
        document.body.appendChild(testElement);
        
        const testFontFamily = window.getComputedStyle(testElement).fontFamily;
        document.body.removeChild(testElement);
        
        if (testFontFamily.includes('bootstrap-icons')) {
            console.log('✅ Bootstrap Icons font is loaded');
        } else {
            console.error('❌ Bootstrap Icons font is NOT loaded!');
        }
        
    }, 2000); // Wait 2 seconds for all styles to load
    
})();