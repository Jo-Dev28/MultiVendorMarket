<?php
/**
 * Google Gemini AI Integration
 * Get API Key from: https://aistudio.google.com/
 */

class GeminiAI {
    private $api_key;
    private $model;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/';
    
    public function __construct($api_key, $model = 'gemini-3.5-flash') {
        $this->api_key = $api_key;
        $this->model = $model;
    }
    
    public function ask($question, $context = null) {
        // Build the prompt with context
        $prompt = $this->buildPrompt($question, $context);
        
        // Prepare the request data
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1000,
                'topP' => 1,
                'topK' => 1
            ]
        ];
        
        // Make the API call
        $url = $this->api_url . $this->model . ':generateContent?key=' . $this->api_key;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Handle the response [citation:4]
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return $result['candidates'][0]['content']['parts'][0]['text'];
            }
        }
        
        // Handle rate limit errors (429) [citation:1]
        if ($httpCode === 429) {
            return "I'm currently experiencing high demand. Please try again in a moment.";
        }
        
        // Handle other errors
        return "Sorry, I encountered an error. Please try again later.";
    }
    
    private function buildPrompt($question, $context) {
        $systemPrompt = "You are an AI shopping assistant for " . SITE_NAME . ". 
        Help customers find products, answer questions about the platform, and provide recommendations.
        
        Platform Information:
        - Website: " . SITE_NAME . "
        - Email: " . ADMIN_EMAIL . "
        - Currency: KSH (Kenyan Shilling)
        
        Rules:
        1. Be helpful, friendly, and professional
        2. Provide accurate product recommendations based on the context
        3. Format responses with proper HTML for display
        4. If you don't know something, say so politely
        
        User Question: " . $question;
        
        if ($context) {
            $systemPrompt .= "\n\nContext about available products:\n" . $context;
        }
        
        return $systemPrompt;
    }
}