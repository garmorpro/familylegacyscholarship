<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<pre>';
    print_r($_POST);
    echo '</pre>';
    exit;
}
?>

<form method="POST" action="">
    <input type="text" name="test_field" value="Hello">
    <button type="submit">Submit</button>
</form>