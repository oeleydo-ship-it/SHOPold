@props(['enabled' => true])

@if($enabled && setting('ai.api_key') && setting('store.auto_translate', false))
<script>
window.AutoTranslate = {
    currentLocale: '{{ app()->getLocale() }}',
    defaultLocale: '{{ setting('store.default_language', 'en') }}',
    csrfToken: '{{ csrf_token() }}',
    
    async translate(text, targetLang = null, sourceLang = null) {
        targetLang = targetLang || this.currentLocale;
        sourceLang = sourceLang || this.defaultLocale;
        
        if (targetLang === sourceLang || !text) return text;
        
        try {
            const response = await fetch('/api/translate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    text: text,
                    target: targetLang,
                    source: sourceLang
                })
            });
            
            const data = await response.json();
            return data.success ? data.translated : text;
        } catch (error) {
            console.error('Translation error:', error);
            return text;
        }
    },
    
    async translateBatch(texts, targetLang = null, sourceLang = null) {
        targetLang = targetLang || this.currentLocale;
        sourceLang = sourceLang || this.defaultLocale;
        
        if (targetLang === sourceLang) return texts;
        
        try {
            const response = await fetch('/api/translate/batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    texts: texts,
                    target: targetLang,
                    source: sourceLang
                })
            });
            
            const data = await response.json();
            return data.success ? data.translated : texts;
        } catch (error) {
            console.error('Translation error:', error);
            return texts;
        }
    },
    
    // Auto-translate elements with data-translate attribute
    async translatePage() {
        if (this.currentLocale === this.defaultLocale) return;
        
        const elements = document.querySelectorAll('[data-translate]');
        if (elements.length === 0) return;
        
        const texts = {};
        elements.forEach((el, index) => {
            const key = el.dataset.translateKey || `text_${index}`;
            texts[key] = el.textContent.trim();
        });
        
        const translated = await this.translateBatch(texts);
        
        elements.forEach((el, index) => {
            const key = el.dataset.translateKey || `text_${index}`;
            if (translated[key]) {
                el.textContent = translated[key];
            }
        });
    }
};

// Auto-translate on page load if needed
document.addEventListener('DOMContentLoaded', function() {
    if (window.AutoTranslate.currentLocale !== window.AutoTranslate.defaultLocale) {
        // Optionally auto-translate elements marked with data-translate
        // window.AutoTranslate.translatePage();
    }
});
</script>
@endif
