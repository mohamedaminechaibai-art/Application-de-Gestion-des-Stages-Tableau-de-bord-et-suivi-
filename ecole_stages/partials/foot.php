</main>

    <footer class="relative z-10 mx-auto max-w-5xl px-4 pb-10 sm:px-6">
        <div class="flex items-center justify-between border-t-2 border-black pt-4 font-mono text-[11px] uppercase tracking-[0.18em] text-black/60">
            <span>Gestion des stages</span>
            <span><?= date('Y') ?></span>
        </div>
    </footer>
</div>


<script>
    // Messages : fermeture manuelle + fermeture automatique des succès
    (() => {
        const close = el => {
            if (!el || el.classList.contains('is-leaving')) return;
            el.classList.add('is-leaving');
            setTimeout(() => el.remove(), 350);
        };
        document.querySelectorAll('[data-alert]').forEach(el => {
            el.querySelector('[data-dismiss]')?.addEventListener('click', () => close(el));
            const delay = parseInt(el.dataset.autoclose, 10);
            if (delay) setTimeout(() => close(el), delay);
        });
    })();
</script>

</body>
</html>