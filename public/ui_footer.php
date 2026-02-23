
<div class="small">
      © <?php echo date("Y"); ?> FitTrack — Personal Fitness Tracker
    </div>
  </div>
  <script src="<?= $BASE_URL ?>/assets/js/app.js"></script>
  <script>
  function showSelfWarning() {
    alert("⚠️ You cannot delete your own account.");
  }

  function protectSelf(userId) {
    const currentUserId = <?php echo isset($_SESSION["user"]) ? (int)$_SESSION["user"]["id"] : 0; ?>;

    if (userId === currentUserId) {
      alert("⚠️ You cannot change your own role.");
      return false;
    }
    return true;
  }
  </script>
</body>
</html>
