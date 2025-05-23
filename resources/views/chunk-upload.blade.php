<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chunk Upload</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">File Upload</h1>

            <div class="bg-white rounded-lg shadow-md p-6">
                <form action="{{ route('chunk.upload') }}"
                      class="dropzone"
                      id="myDropzone"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="fallback">
                        <input name="file" type="file" multiple />
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        Dropzone.autoDiscover = false;

        const myDropzone = new Dropzone("#myDropzone", {
            url: "{{ route('chunk.upload') }}",
            chunking: true,
            forceChunking: true,
            chunkSize: 1024 * 1024, // 1MB chunks
            parallelChunkUploads: false, // Disable parallel uploads
            retryChunks: true,
            retryChunksLimit: 3,
            maxFilesize: 1024, // 1GB
            addRemoveLinks: true,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            init: function() {
                this.on("sending", function(file, xhr, formData) {
                    formData.append("uniqueId", file.upload.uuid);
                    formData.append("_token", document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                });

                this.on("success", function(file, response) {
                    console.log("Chunk uploaded successfully:", response);
                });

                this.on("error", function(file, errorMessage) {
                    console.error("Upload error:", errorMessage);
                });

                this.on("complete", function(file) {
                    if (file.status === "success") {
                        console.log("File upload completed");
                    }
                });
            }
        });
    </script>
</body>
</html>
