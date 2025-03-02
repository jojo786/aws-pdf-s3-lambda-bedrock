import json
import boto3

# Initialize S3 and Bedrock clients
s3 = boto3.client('s3')
bedrock = boto3.client('bedrock-runtime')


def lambda_handler(event, context):
    try:
        # Get bucket and key from event
        bucket = event['Records'][0]['s3']['bucket']['name']
        key = event['Records'][0]['s3']['object']['key']
        print(f"Original key: {key}")
        
        # Get PDF from S3
        response = s3.get_object(Bucket=bucket, Key=key)
        pdf_content = response['Body'].read()

        # Remove the .pdf extension from the key
        doc_name = key.rsplit('.', 1)[0]  # This splits on the last '.' and takes the first part
        print(f"Document name: {doc_name}")
        
        # Prepare messages for Bedrock Converse API
        messages = [
            {
                "role": "user",
                "content": [
                    {
                        "text": "Please analyze this PDF document"
                    },
                    {
                        "document": {
                            "format": "pdf",
                            "name": doc_name, # Using the key name without extension
                            "source": {
                                "bytes": pdf_content
                            }
                        }
                    }
                ]
            }
        ]

        # Use the converse API with direct parameters
        response = bedrock.converse(
             modelId='us.anthropic.claude-3-5-sonnet-20241022-v2:0',  # Updated to Claude 3.5 Sonnet
            messages=messages
        )
        
         # Extract the text from the response
        response_text = response['output']['message']['content'][0]['text']
        print("Bedrock Analysis:", response_text)
        
        
    except Exception as e:
        print(f"Error: {str(e)}")
        return {
            'statusCode': 500,
            'body': json.dumps({
                'error': str(e)
            })
        }