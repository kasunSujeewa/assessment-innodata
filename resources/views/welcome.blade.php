<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import CSV</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-lg">
        <h1 class="text-2xl font-semibold mb-4">Import CSV</h1>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-200 text-green-700 border border-green-300 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-200 text-red-700 border border-red-300 rounded">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="upload-form" action="{{ route('csv file upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="flex items-center space-x-4">
                <label for="file" class="block text-gray-700">CSV File:</label>
                <input type="file" name="file" id="file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md">
            </div>

            <button type="submit" class="w-full py-2 px-4 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                Import
            </button>
        </form>

        <div id="progress-container" class="hidden mt-4">
            <p id="progress-text" class="mb-2 text-gray-700">Processing...</p>
            <div class="relative pt-1">
                <div class="flex mb-2 items-center justify-between">
                    <div class="text-xs font-medium text-blue-600" id="progress-percentage">0%</div>
                </div>
                <div class="flex">
                    <div id="progress-fill" class="bg-blue-500 text-xs leading-none py-1 text-center text-white rounded"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('upload-form');
            const progressContainer = document.getElementById('progress-container');
            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');
            const progressPercentage = document.getElementById('progress-percentage');

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(form);
                const xhr = new XMLHttpRequest();

                xhr.open('POST', form.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                       
                        const jobId = response.jobId;
                        
                        progressContainer.classList.remove('hidden');
                        checkProgress(jobId);
                    }
                };

                xhr.send(formData);
            });

            function checkProgress(jobId) {
                const interval = setInterval(function () {
                    fetch(`/job-progress/${jobId}`)
                        .then(response => response.json())
                        .then(data => {
                            const progress = data.progress;
                            progressFill.style.width = progress + '%';
                            progressPercentage.textContent = progress + '%';
                            progressText.textContent = progress === 100 ? 'Processing complete!' : 'Processing...';

                            if (progress === 100) {
                                clearInterval(interval);
                            }
                        });
                }, 1000); 
            }
        });
    </script>
</body>
</html>
