<!-- DEPRECATED: Use new language-toggle.js system instead -->
<script>
// Redirect to new language system
if (typeof window.languageToggle === 'undefined') {
    console.warn('Loading new language system...');
    const script = document.createElement('script');
    script.src = '../../assets/js/language-toggle.js';
    document.head.appendChild(script);
}
</script>