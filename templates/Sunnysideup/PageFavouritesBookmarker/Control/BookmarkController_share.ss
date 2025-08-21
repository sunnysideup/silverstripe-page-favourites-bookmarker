<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Redirecting...</title>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Your script logic here
      localStorage.setItem('pf-store-updated-bookmark-list', JSON.stringify($BookmarkList.RAW));

      // Redirect to homepage after script
      window.location.href = '$RedirectURL';
    });
  </script>
</head>
<body>
  <p>Loading ...</p>
</body>
</html>
