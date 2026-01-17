<?php
$conn = mysqli_connect("localhost", "root", "", "quan_ly_phong_hoc");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
