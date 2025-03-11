<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Panadería El Buen Gusto - Admin</title>

        <!-- Favicon -->
        <link rel="icon" href="{{ asset('panaderia.png') }}" type="image/png"/>
        
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- DataTables CSS -->
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
        
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
        <div id="app"></div>
        
        <!-- DataTables JS -->
        <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
        
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        
        <script src="{{ mix('js/app.js') }}"></script>
        
        <script>
            // Inicializar DataTables después de que la página se cargue
            $(document).ready(function() {
                setTimeout(function() {
                    if ($.fn.DataTable && $('#blogsTable').length) {
                        if (!$.fn.DataTable.isDataTable('#blogsTable')) {
                            $('#blogsTable').DataTable();
                        }
                    }
                }, 1000);
            });
        </script>
    </body>
</html>
