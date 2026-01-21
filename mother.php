<?php
include "db.php";

if (isset($_POST['save_mother'])) {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $blood = $_POST['blood'];
    $nid = $_POST['nid'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];

    $sql = "INSERT INTO mothers (mother_name,age,blood_group,, nid_number, mobile_number, address)
            VALUES ('$name', '$age', '$blood','$nid','$mobile', '$address')";
    $conn->query($sql);
}
?>
