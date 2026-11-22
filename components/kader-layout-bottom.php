            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Modal helper sederhana
        function openModal(id)  { var m = document.getElementById(id); if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; } }
        function closeModal(id) { var m = document.getElementById(id); if (m) { m.classList.remove('open'); document.body.style.overflow = ''; } }
        document.addEventListener('click', function (ev) {
            if (ev.target.classList && ev.target.classList.contains('modal-overlay')) closeModal(ev.target.id);
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(function (m) { closeModal(m.id); });
        });
    </script>
    <?php if (!empty($page_scripts)) echo $page_scripts; ?>
</body>

</html>
