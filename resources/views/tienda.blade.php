<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panadería El Buen Gusto - Tienda</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('panaderia.png') }}" type="image/png"/>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- QRCode -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.0/build/qrcode.min.js"></script>
    
    <!-- JSBarcode -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    
    <!-- Estilos personalizados -->
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #F5E6D3;
        }
        .bg-brown {
            background-color: #8B4513;
        }
        .text-brown {
            color: #8B4513;
        }
        .border-brown {
            border-color: #8B4513;
        }
    </style>
</head>
<body>
    <div id="client-app"></div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="{{ mix('js/client-app.js') }}"></script>
</body>
</html>
