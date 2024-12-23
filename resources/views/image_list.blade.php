<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload and View Images</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row vh-100">
            <!-- Left Section: Upload Form -->
            <div class="col-md-6 d-flex flex-column justify-content-center align-items-center bg-light border-end">
                <h2 class="mb-4">Upload Excel File</h2>
                <form action="{{route('write.excel')}}" method="POST" enctype="multipart/form-data" class="w-75">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="excelFile" accept=".xlsx, .xls" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Upload</button>
                </form>
            </div>

            <!-- Right Section: Display Images -->
            <div class="col-md-6 d-flex flex-column justify-content-center align-items-center">
                <h2 class="mb-4">Image List</h2>
                <ul class="list-group w-75">
                    @foreach($images as $image)
                    <li class="list-group-item d-flex align-items-center">
                        <img src="{{ $image['src'] }}" alt="{{ $image['name'] }}" class="img-thumbnail me-3" style="width: 100px; height: auto;">
                        <a href="{{ route('image.download', ['filename' => $image['name']]) }}" class="btn btn-primary">Download</a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
