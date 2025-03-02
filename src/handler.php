<?php

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\BedrockRuntime\BedrockRuntimeClient;

// Initialize S3 and Bedrock clients
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1'
]);

$bedrock = new BedrockRuntimeClient([
    'version' => 'latest',
    'region'  => 'us-east-1'
]);

function lambda_handler($event, $context) {
    try {
        // Get bucket and key from event
        $bucket = $event['Records'][0]['s3']['bucket']['name'];
        $key = $event['Records'][0]['s3']['object']['key'];
        echo "Original key: $key\n";
        
        // Get PDF from S3
        $response = $s3->getObject([
            'Bucket' => $bucket,
            'Key' => $key
        ]);
        $pdf_content = $response['Body']->getContents();

        // Remove the .pdf extension from the key
        $doc_name = explode('.', $key)[0];
        echo "Document name: $doc_name\n";
        
        // Prepare messages for Bedrock Converse API
        $messages = [
            [
                "role" => "user",
                "content" => [
                    [
                        "text" => "Please analyze this PDF document"
                    ],
                    [
                        "document" => [
                            "format" => "pdf",
                            "name" => $doc_name,
                            "source" => [
                                "bytes" => $pdf_content
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Use the converse API with direct parameters
        $response = $bedrock->converse([
            'modelId' => 'us.anthropic.claude-3-5-sonnet-20241022-v2:0',
            'messages' => $messages
        ]);
        
        // Extract the text from the response
        $response_text = $response['output']['message']['content'][0]['text'];
        echo "Bedrock Analysis: $response_text\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return [
            'statusCode' => 500,
            'body' => json_encode([
                'error' => $e->getMessage()
            ])
        ];
    }
}
?>