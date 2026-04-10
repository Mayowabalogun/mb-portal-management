</main>
<footer class="footer mt-auto py-3 bg-light border-top">
  <div class="container text-center">
    <span class="text-muted">&copy; <?= date('Y') ?> MB PropertyFinder — All Rights Reserved.</span>
  </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= BASE_URL ?>/public/assets/js/app.js"></script>

<?php if (!empty($page_scripts)): ?>
  <?php foreach ($page_scripts as $script): ?>
    <script src="<?= htmlspecialchars((string) $script) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
