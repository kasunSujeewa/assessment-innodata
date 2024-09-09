<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import CSV</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- Include jQuery and DataTables CSS/JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-8">
    <div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-3xl space-y-8">
        <div class="border-b pb-4 mb-4">
            <h1 class="text-3xl font-bold text-center text-gray-800">Import CSV</h1>
        </div>

        <!-- Success and Error Messages -->
        @if (session('success'))
            <div class="p-4 mb-4 bg-green-100 text-green-800 rounded border border-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 mb-4 bg-red-100 text-red-800 rounded border border-red-300">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- CSV Upload Form -->
        <form id="upload-form" action="{{ route('csv file upload') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <div class="flex flex-col space-y-2">
                <label for="file" class="text-gray-700 font-medium">CSV File</label>
                <input type="file" name="file" id="file" class="border border-gray-300 rounded-md p-2 w-full text-gray-800">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-md font-semibold hover:bg-blue-600 transition duration-300">
                Import
            </button>
        </form>

        <!-- Progress Bar -->
        <div id="progress-container" class="hidden mt-8">
            <p id="progress-text" class="text-gray-700 font-medium mb-2">Processing...</p>
            <div class="relative w-full h-4 bg-gray-200 rounded overflow-hidden">
                <div id="progress-fill" class="absolute left-0 top-0 h-full bg-blue-500" style="width: 0;"></div>
            </div>
            <div class="text-right text-xs font-medium text-gray-600 mt-2" id="progress-percentage">0%</div>
        </div>

        <!-- Error message container -->
<div id="error-container" class="hidden text-red-500"></div>

        <!-- Users Table -->
        <div class="mt-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Users List</h2>
            <table id="users-table" class="min-w-full bg-white shadow-md rounded-lg overflow-hidden">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-200">
                        <th class="py-3 px-6 text-left text-sm font-semibold text-gray-600">ID</th>
                        <th class="py-3 px-6 text-left text-sm font-semibold text-gray-600">Name</th>
                        <th class="py-3 px-6 text-left text-sm font-semibold text-gray-600">Email</th>
                        <th class="py-3 px-6 text-left text-sm font-semibold text-gray-600">Contact No</th>
                        <th class="py-3 px-6 text-left text-sm font-semibold text-gray-600">Address</th>
                        <th class="py-3 px-6 text-left text-sm font-semibold text-gray-600">Created At</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            const usersTable = $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('users.data') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'name' },
                    { data: 'email', name: 'email' },
                    { data: 'contact_no', name: 'contact_no' },
                    { data: 'address', name: 'address' },
                    { data: 'created_at', name: 'created_at' }
                ]
            });
        });
    </script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('upload-form');
        const progressContainer = document.getElementById('progress-container');
        const progressFill = document.getElementById('progress-fill');
        const progressText = document.getElementById('progress-text');
        const progressPercentage = document.getElementById('progress-percentage');
        const errorContainer = document.getElementById('error-container');

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Clear previous error messages
            errorContainer.classList.add('hidden');
            errorContainer.textContent = '';

            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();

            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.error) {
                        // Show server-side validation errors
                        showError(response.error);
                    } else {
                        const jobId = response.jobId;

                        progressContainer.classList.remove('hidden');
                        checkProgress(jobId);
                    }
                } 
                else if (xhr.status === 422) {
                    const response = JSON.parse(xhr.responseText);
                    console.log(response);
                    if (response.errors) {
                        const errors = response.errors;
                        showError(errors);
                    }
            }else {
                    showError('An error occurred while uploading the file. Please try again.');
                }
            };

            xhr.onerror = function () {
                showError('An error occurred while uploading the file. Please check your network connection and try again.');
            };

            xhr.send(formData);
        });

        function checkProgress(jobId) {
            const interval = setInterval(function () {
                fetch(`/job-progress/${jobId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Failed to fetch job progress.');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            showError(data.error);
                            clearInterval(interval);
                            return;
                        }

                        const progress = data.progress;
                        progressFill.style.width = progress + '%';
                        progressPercentage.textContent = progress + '%';
                        progressText.textContent = progress === 100 ? 'Processing complete!' : 'Processing...';

                        if (progress === 100) {
                            clearInterval(interval);
                            const usersTable = $('#users-table').DataTable();
                            usersTable.ajax.reload();
                        }
                    })
                    .catch(error => {
                        showError(error.message);
                        clearInterval(interval);
                    });
            }, 1000);
        }

        function showError(message) {
            errorContainer.textContent = message;
            errorContainer.classList.remove('hidden');
        }
    });
</script>

</body>
</html>
