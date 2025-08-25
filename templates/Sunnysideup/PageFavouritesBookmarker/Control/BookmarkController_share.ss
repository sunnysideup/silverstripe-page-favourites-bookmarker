<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Redirecting...</title>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Your script logic here
      window.localStorage.setItem('$NameOfTemporarySharedStore', JSON.stringify($BookmarkListAsJson.RAW));

      // Redirect to homepage after script
      window.location.href = '$RedirectURL';
    });
  </script>
</head>
<body>
  <p>Loading ...</p>
</body>
</html>
