<?php
session_start();

// Calculate cart count
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['cartCount' => $cartCount]);

    
