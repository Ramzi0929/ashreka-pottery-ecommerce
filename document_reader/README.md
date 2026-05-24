# Heritage Document Reader

This folder contains the document reading functionality for the heritage system.

## Installation

### Option 1: Run the installer (Windows)
Double-click `install.bat` to automatically install the packages.

### Option 2: Manual installation
1. Open Command Prompt
2. Navigate to this folder:
   ```
   cd "c:\xampp\htdocs\ashreka-pottery-system advanced\document_reader"
   ```
3. Run composer install:
   ```
   composer install
   ```

## What it does

- **PDF files**: Extracts actual text content from PDFs
- **Word documents**: Extracts text from .doc/.docx files  
- **Text files**: Reads content directly
- **Fallback**: If packages aren't installed, shows descriptions only

## File Structure

```
document_reader/
├── composer.json          # Package dependencies
├── install.bat            # Windows installer
├── src/
│   └── DocumentReader.php # Main document reader class
└── vendor/                # Composer packages (after install)
```

## Usage

The system automatically detects if document reader packages are installed:
- **With packages**: Shows extracted text content
- **Without packages**: Shows descriptions with download buttons

## Requirements

- PHP 7.4 or higher
- Composer installed on your system
- Internet connection for package download