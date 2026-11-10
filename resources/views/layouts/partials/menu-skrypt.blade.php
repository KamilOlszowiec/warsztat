<script>
(function () {
    const menu = document.getElementById('menu-boczne');
    const tlo = document.getElementById('menu-tlo');
    const otworz = document.getElementById('menu-otworz');
    const zamknij = document.getElementById('menu-zamknij');

    function ustaw(otwarte) {
        menu.classList.toggle('-translate-x-full', !otwarte);
        tlo.classList.toggle('hidden', !otwarte);
        otworz.setAttribute('aria-expanded', otwarte ? 'true' : 'false');
        document.body.classList.toggle('overflow-hidden', otwarte);
    }

    otworz.addEventListener('click', () => ustaw(true));
    zamknij.addEventListener('click', () => ustaw(false));
    tlo.addEventListener('click', () => ustaw(false));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') ustaw(false); });
    // Po przejściu na szeroki ekran menu nie może zostać „otwarte” z zablokowanym przewijaniem
    window.matchMedia('(min-width: 1024px)').addEventListener('change', e => { if (e.matches) ustaw(false); });
})();
</script>
