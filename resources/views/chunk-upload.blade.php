<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>File Upload</title>
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
    <style>
        .dropzone {
            border: 2px dashed #0087F7;
            border-radius: 5px;
            background: white;
        }
        .dropzone .dz-message {
            font-weight: 400;
        }
        .dropzone .dz-preview .dz-details {
            color: #2c3e50;
        }
        .upload-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .upload-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .upload-header h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .upload-header p {
            color: #7f8c8d;
            font-size: 16px;
        }
        .dz-success-mark, .dz-error-mark {
            display: none;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <div class="upload-header">
            <h1>File Upload</h1>
            <p>Upload your Image, PDF, DOCX, or XLSX files here</p>
        </div>

        <form action="{{ route('chunk.upload') }}"
              class="dropzone"
              id="myDropzone"
              enctype="multipart/form-data">
            @csrf
            <div class="fallback">
                <input name="file" type="file" multiple />
            </div>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('files.list') }}" class="text-blue-500 hover:text-blue-700">
                View Uploaded Files
            </a>
        </div>
    </div>

    <script>
        Dropzone.autoDiscover = false;

        // Define file size constants
        const MAX_FILE_SIZE = 1024; // 1GB in MB
        const CHUNK_SIZE = 1; // 1MB chunks

        const myDropzone = new Dropzone("#myDropzone", {
            url: "{{ route('chunk.upload') }}",
            chunking: true,
            forceChunking: true,
            chunkSize: CHUNK_SIZE * 1024 * 1024, // Convert to bytes
            parallelChunkUploads: false, // Disable parallel uploads
            retryChunks: true,
            retryChunksLimit: 3,
            maxFilesize: MAX_FILE_SIZE, // 1GB
            addRemoveLinks: true,
            acceptedFiles: '.pdf,.docx,.xlsx,.jpg,.jpeg,.png,.webp',
            dictDefaultMessage: "Drop files here or click to upload",
            dictFallbackMessage: "Your browser does not support drag'n'drop file uploads.",
            dictFileTooBig: `File is too big (${MAX_FILE_SIZE}MB). Max filesize: ${MAX_FILE_SIZE}MB.`,
            dictInvalidFileType: "You can't upload files of this type.",
            dictResponseError: "Server responded with error code.",
            dictCancelUpload: "Cancel upload",
            dictUploadCanceled: "Upload canceled.",
            dictCancelUploadConfirmation: "Are you sure you want to cancel this upload?",
            dictRemoveFile: "Remove file",
            dictMaxFilesExceeded: "You can't upload any more files.",
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            init: function() {
                this.on("sending", function(file, xhr, formData) {
                    // Calculate chunk index
                    const chunkIndex = Math.floor(file.upload.loaded / this.options.chunkSize);
                    const totalChunks = Math.ceil(file.size / this.options.chunkSize);

                    formData.append("uniqueId", file.upload.uuid);
                    formData.append("name", file.name);
                    formData.append("chunk", chunkIndex);
                    formData.append("chunks", totalChunks);
                    formData.append("_token", document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                    // Log chunk information
                    console.log('Uploading chunk:', {
                        file: file.name,
                        chunkIndex: chunkIndex,
                        totalChunks: totalChunks,
                        loaded: file.upload.loaded,
                        total: file.size,
                        uuid: file.upload.uuid
                    });
                });

                this.on("uploadprogress", function(file, progress) {
                    const chunkIndex = Math.floor(file.upload.loaded / this.options.chunkSize);
                    const totalChunks = Math.ceil(file.size / this.options.chunkSize);

                    console.log('Upload progress:', {
                        file: file.name,
                        progress: progress,
                        chunkIndex: chunkIndex,
                        totalChunks: totalChunks,
                        loaded: file.upload.loaded,
                        total: file.size
                    });
                });

                this.on("success", function(file, response) {
                    console.log("Chunk uploaded successfully:", response);
                });

                this.on("error", function(file, errorMessage) {
                    console.error("Upload error:", errorMessage);
                    if (typeof errorMessage === 'object') {
                        alert(errorMessage.message || "Upload failed");
                    } else {
                        alert(errorMessage);
                    }
                });

                this.on("complete", function(file) {
                    if (file.status === "success") {
                        console.log("File upload completed");
                    }
                });
            },
            // Add these options to ensure proper chunk handling
            maxFiles: 5,
            timeout: 0,
            uploadMultiple: false,
            createImageThumbnails: false,
            autoProcessQueue: true,
            addRemoveLinks: true,
            dictRemoveFile: "Remove",
            dictCancelUpload: "Cancel",
            dictCancelUploadConfirmation: "Are you sure you want to cancel this upload?",
            dictMaxFilesExceeded: "You can't upload any more files.",
            dictFileTooBig: `File is too big (${MAX_FILE_SIZE}MB). Max filesize: ${MAX_FILE_SIZE}MB.`,
            dictInvalidFileType: "You can't upload files of this type.",
            dictResponseError: "Server responded with error code.",
            dictUploadCanceled: "Upload canceled.",
            dictDefaultMessage: "Drop files here or click to upload",
            dictFallbackMessage: "Your browser does not support drag'n'drop file uploads."
        });
    </script>
</body>
</html>
