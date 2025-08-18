<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collect Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Provide Your Email</h1>
        <form method="POST" action="/auth/collect-email">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <input type="hidden" name="provider" value="{{ request('provider') }}">
            <input type="hidden" name="sub" value="{{ request('sub') }}">
            <button type="submit" class="btn btn-primary w-100">Submit</button>
        </form>
    </div>
</body>
</html>
