<?php
namespace Heritage\DocumentReader;

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;

class DocumentReader {
    
    public static function extractText($filePath) {
        if (!file_exists($filePath)) {
            return "File not found: " . $filePath;
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        try {
            switch ($extension) {
                case 'txt':
                    return file_get_contents($filePath);
                    
                case 'pdf':
                    return self::extractPdfText($filePath);
                    
                case 'doc':
                case 'docx':
                    return self::extractWordText($filePath);
                    
                default:
                    return "Unsupported file type: " . $extension;
            }
        } catch (\Exception $e) {
            return "Error reading file: " . $e->getMessage();
        }
    }
    
    private static function extractPdfText($filePath) {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            if (empty($text)) {
                return "PDF file appears to be empty or contains only images.";
            }
            
            return $text;
        } catch (\Exception $e) {
            return "Could not read PDF: " . $e->getMessage();
        }
    }
    
    private static function extractWordText($filePath) {
        try {
            $phpWord = IOFactory::load($filePath);
            $text = '';
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    } elseif (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $childElement) {
                            if (method_exists($childElement, 'getText')) {
                                $text .= $childElement->getText() . " ";
                            }
                        }
                        $text .= "\n";
                    }
                }
            }
            
            if (empty(trim($text))) {
                return "Word document appears to be empty or could not extract text.";
            }
            
            return trim($text);
        } catch (\Exception $e) {
            return "Could not read Word document: " . $e->getMessage();
        }
    }
}