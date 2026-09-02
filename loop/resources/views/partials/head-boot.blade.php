{{--
    Critical CSS + boot flag must run before first paint.
    Stops the full-bleed “page looks huge, then snaps to the shell” flash
    while Vite/Tailwind and Alpine are still arriving.
--}}
<style>
    [x-cloak]{display:none!important}
    html{
        -webkit-text-size-adjust:100%;
        text-size-adjust:100%;
        overflow-x:hidden;
        background:#F7F7F4;
    }
    body{
        margin:0;
        background:#F7F7F4;
        color:#111114;
        font-family:"DM Sans",ui-sans-serif,system-ui,sans-serif;
    }
    input,select,textarea{font-size:16px}
    .hidden{display:none!important}
    .flex{display:flex}
    .inline-flex{display:inline-flex}
    .grid{display:grid}
    @media (min-width:640px){
        .sm\:hidden{display:none!important}
        .sm\:flex{display:flex!important}
        .sm\:block{display:block!important}
        .sm\:inline-flex{display:inline-flex!important}
        .sm\:grid{display:grid!important}
    }
    @media (min-width:768px){
        .md\:hidden{display:none!important}
        .md\:flex{display:flex!important}
        .md\:block{display:block!important}
        .md\:grid{display:grid!important}
        .md\:flex-col{flex-direction:column}
    }
    @media (min-width:1024px){
        .lg\:hidden{display:none!important}
        .lg\:flex{display:flex!important}
        .lg\:block{display:block!important}
        .lg\:grid{display:grid!important}
        .lg\:invisible{visibility:hidden}
    }
    .loop-shell{
        box-sizing:border-box;
        width:100%;
        max-width:72rem;
        margin-left:auto;
        margin-right:auto;
        padding-left:1rem;
        padding-right:1rem;
    }
    .loop-guest-card,
    .loop-onboard{
        box-sizing:border-box;
        width:100%;
        margin-left:auto;
        margin-right:auto;
    }
    .loop-guest-card{max-width:28rem}
    .loop-onboard{max-width:32rem}
    @media (min-width:768px){
        .loop-guest-split{
            display:grid;
            grid-template-columns:1fr 1fr;
            min-height:100vh;
        }
    }
    .loop-page-skeleton{
        position:fixed;
        inset:0;
        z-index:100;
        display:none;
        flex-direction:column;
        background:#F7F7F4;
        pointer-events:none;
        opacity:0;
        visibility:hidden;
    }
    html.loop-js:not(.loop-ready):not(.loop-no-skeleton) .loop-page-skeleton{
        display:flex;
        opacity:1;
        visibility:visible;
        pointer-events:auto;
    }
    /* Do not zero the real page. The overlay covers the wait; hiding
       content caused a second flash (and an enlarge-snap) when ready. */
    html.loop-ready .loop-page-skeleton,
    html.loop-no-skeleton .loop-page-skeleton{
        display:none !important;
        opacity:0 !important;
        visibility:hidden !important;
        pointer-events:none !important;
    }
    .sr-only{
        position:absolute;
        width:1px;
        height:1px;
        padding:0;
        margin:-1px;
        overflow:hidden;
        clip:rect(0,0,0,0);
        white-space:nowrap;
        border:0;
    }
    .loop-skel-bone{
        display:block;
        border-radius:1rem;
        background:#ecece8;
    }
    .admin-html body,
    .loop-page-skeleton[data-variant="admin"]{
        background:#f1f5f9;
    }
    .loop-skel-app,
    .loop-skel-public{width:100%;max-width:72rem;margin:0 auto;padding:0 1rem 2rem}
    .loop-skel-bar{height:3.5rem;margin:0 -1rem 1.5rem;border-radius:0;background:#fff}
    .loop-skel-hero{height:10.5rem;border-radius:1.75rem;background:#1a1228}
    .loop-skel-stats{display:grid;grid-template-columns:1fr 1fr;gap:.85rem}
    .loop-skel-stat{height:7.25rem}
    .loop-skel-row{height:4.25rem;margin-top:.75rem}
    .loop-skel-app__body{display:flex;flex-direction:column;gap:1rem}
    .loop-skel-guest,.loop-skel-admin{display:flex;min-height:100%}
    .loop-skel-guest__form{display:flex;flex-direction:column;gap:.85rem;margin:auto;width:min(28rem,100%);padding:2rem 1.5rem}
    .loop-skel-title{height:2rem;width:12rem}
    .loop-skel-input,.loop-skel-btn{height:3.25rem}
    .loop-skel-guest__aside,.loop-skel-admin__rail{display:none}
    .loop-skel-admin__main{flex:1;padding:0 1.25rem}
    .loop-skel-display{height:3.25rem;width:70%;max-width:16rem;margin-top:1rem}
    .loop-skel-copy{height:.9rem;width:85%;max-width:22rem;margin-top:.75rem}
    .loop-skel-card{height:9rem;margin-top:1rem}

</style>
<script>
    (function () {
        var root = document.documentElement;
        root.classList.add('loop-js');
        if (root.classList.contains('loop-no-skeleton')) {
            return;
        }
        try {
            var kind = sessionStorage.getItem('loopNavKind') || '';
            if (kind) {
                root.setAttribute('data-loop-nav', kind);
                root.classList.add('loop-ready');
                try {
                    if (history.scrollRestoration) {
                        history.scrollRestoration = kind === 'back' ? 'auto' : 'manual';
                    }
                    if (kind !== 'back') {
                        window.scrollTo(0, 0);
                    }
                } catch (e2) {}
                return;
            }
            root.classList.add('loop-nav-pending');
        } catch (e) {
            root.classList.add('loop-nav-pending');
        }
    })();
</script>
<noscript>
    <style>
        html.loop-js:not(.loop-ready) body > :not(.loop-page-skeleton){opacity:1!important}
        .loop-page-skeleton{display:none!important}
    </style>
</noscript>
