<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploaded Files</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .file-list {
            list-style: none;
            padding: 0;
        }
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .file-item:last-child {
            border-bottom: none;
        }
        .file-info {
            flex-grow: 1;
        }
        .file-name {
            font-weight: bold;
            color: #2c3e50;
        }
        .file-size {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .file-actions a {
            color: #3498db;
            text-decoration: none;
            margin-left: 10px;
        }
        .file-actions a:hover {
            text-decoration: underline;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .no-files {
            text-align: center;
            color: #7f8c8d;
            padding: 20px;
        }
        .loading {
            text-align: center;
            color: #7f8c8d;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('chunk.upload') }}" class="back-link">← Back to Upload</a>
        <h1>Uploaded Files</h1>

        <div id="fileList">
            <div class="loading">Loading files...</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetchFiles();
        });

        function fetchFiles() {
            fetch('/api/files')
                .then(response => response.json())
                .then(files => {
                    const fileListElement = document.getElementById('fileList');

                    // Debug information
                    console.log('Files received:', files);

                    if (files.length === 0) {
                        fileListElement.innerHTML = '<div class="no-files">No files have been uploaded yet.</div>';
                        return;
                    }

                    const fileList = document.createElement('ul');
                    fileList.className = 'file-list';

                    files.forEach(file => {
                        const fileItem = document.createElement('li');
                        fileItem.className = 'file-item';

                        const fileSize = (file.size / 1024 / 1024).toFixed(2);

                        // Debug information for each file
                        console.log('File details:', {
                            name: file.name,
                            path: file.path,
                            url: file.url,
                            size: fileSize
                        });

                        fileItem.innerHTML = `
                            <div class="file-info">
                                <div class="file-name">${file.name}</div>
                                <div class="file-size">${fileSize} MB</div>
                            </div>
                            <div class="file-actions">
                                <a href="${file.url}" download>Download</a>
                            </div>
                        `;

                        fileList.appendChild(fileItem);
                    });

                    fileListElement.innerHTML = '';
                    fileListElement.appendChild(fileList);
                })
                .catch(error => {
                    console.error('Error fetching files:', error);
                    document.getElementById('fileList').innerHTML =
                        '<div class="no-files">Error loading files. Please try again later.</div>';
                });
        }
    </script>
</body>
</html>
