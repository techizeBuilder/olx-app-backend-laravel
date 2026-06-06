<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->apiUrl = config('services.gemini.api_url');
    }

    /**
     * Send prompt to Gemini API and return response
     */
    public function generateContent(string $prompt): array
    {
        try {
            if (empty($this->apiKey)) {
                Log::error('Gemini API Error: Missing API key.');
                return ['success' => false, 'error' => 'Missing Gemini API key'];
            }

            $endpoint = $this->apiUrl;
            if (!str_contains($endpoint, ':generateContent')) {
                $endpoint .= ':generateContent';
            }

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->post($endpoint, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 500,
                        'temperature' => 0.7,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Gemini API HTTP Error', ['status' => $response->status(), 'body' => $response->body()]);

                $errorMessage = 'Content generation currently not available, please try again later.';
                if ($response->status() === 429) {
                    $errorMessage = 'API quota exceeded. Please check your Gemini API plan and billing details.';
                } elseif ($response->status() === 401 || $response->status() === 403) {
                    $errorMessage = 'Invalid API key. Please check your Gemini API key in settings.';
                }

                return ['success' => false, 'error' => $errorMessage];
            }

            return ['success' => true, 'data' => $response->json()];

        } catch (\Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to generate content: ' . $e->getMessage()];
        }
    }

    /**
     * Generate item description using Gemini AI
     */
    public function generateDescription(array $data): array
    {
        try {
            $prompt = $this->buildDescriptionPrompt($data);
            $promptHash = md5($prompt);

            $languageSuffix = $this->getLanguageSuffix($data);
            $cacheKey = "gemini_description_{$promptHash}{$languageSuffix}";

            $cached = Cache::store('gemini')->get($cacheKey);
            if ($cached) {
                return ['success' => true, 'data' => $cached, 'cached' => true];
            }

            $result = $this->generateContent($prompt);
            if (!$result['success']) {
                return $result;
            }

            $content = $this->extractText($result['data']);
            $content = $this->cleanResponse($content);

            Cache::store('gemini')->put($cacheKey, $content, 86400);

            return [
                'success' => true,
                'data' => $content,
                'cached' => false,
                'tokens_used' => $this->estimateTokens($prompt . $content),
            ];
        } catch (\Exception $e) {
            Log::error('Gemini Description Generation Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to generate description: ' . $e->getMessage()];
        }
    }

    /**
     * Generate SEO meta details using Gemini AI
     */
    public function generateMetaDetails(array $data): array
    {
        try {
            $prompt = $this->buildMetaPrompt($data);
            $promptHash = md5($prompt);

            $languageSuffix = $this->getLanguageSuffix($data);
            $cacheKey = "gemini_meta_{$promptHash}{$languageSuffix}";

            $cached = Cache::store('gemini')->get($cacheKey);
            if ($cached) {
                return ['success' => true, 'data' => $cached, 'cached' => true];
            }

            $result = $this->generateContent($prompt);
            if (!$result['success']) {
                return $result;
            }

            $content = $this->extractText($result['data']);
            $content = $this->cleanResponse($content);

            $decoded = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $response = [
                    'meta_title' => $decoded['meta_title'] ?? '',
                    'meta_description' => $decoded['meta_description'] ?? '',
                    'meta_keywords' => $decoded['meta_keywords'] ?? '',
                ];
            } else {
                $response = $this->parseMetaFromText($content);
            }

            Cache::store('gemini')->put($cacheKey, $response, 86400);

            return [
                'success' => true,
                'data' => $response,
                'cached' => false,
                'tokens_used' => $this->estimateTokens($prompt . json_encode($response)),
            ];
        } catch (\Exception $e) {
            Log::error('Gemini Meta Generation Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to generate meta details: ' . $e->getMessage()];
        }
    }

    /**
     * Check if cached description exists
     */
    public function hasCachedDescription(array $data): bool
    {
        $prompt = $this->buildDescriptionPrompt($data);
        $cacheKey = "gemini_description_" . md5($prompt) . $this->getLanguageSuffix($data);
        return Cache::store('gemini')->has($cacheKey);
    }

    /**
     * Check if cached meta details exist
     */
    public function hasCachedMetaDetails(array $data): bool
    {
        $prompt = $this->buildMetaPrompt($data);
        $cacheKey = "gemini_meta_" . md5($prompt) . $this->getLanguageSuffix($data);
        return Cache::store('gemini')->has($cacheKey);
    }

    /**
     * Build description generation prompt
     */
    public function buildDescriptionPrompt(array $data): string
    {
        $prompt = "Write an SEO-friendly item listing description (200-300 words).\n";

        // Add data first
        $prompt .= "\nTitle: " . trim($data['title'] ?? 'N/A');

        $fields = [];
        if (!empty($data['location'])) $fields[] = "Location: {$data['location']}";
        if (!empty($data['city'])) $fields[] = "City: {$data['city']}";
        if (!empty($data['state'])) $fields[] = "State: {$data['state']}";
        if (!empty($data['country'])) $fields[] = "Country: {$data['country']}";
        if (!empty($data['price'])) $fields[] = "Price: {$data['price']}";
        if (!empty($data['category_name'])) $fields[] = "Category: {$data['category_name']}";
        if (!empty($data['currency_iso_code'])) {
            $currency = $data['currency_iso_code'];
            $fields[] = "Currency: {$currency}";
            $prompt .= "\n\nIMPORTANT: Write the description in {$currency} currency using its symbol.";
        } else {
            $currency = CachingService::getSystemSettings('currency_iso_code');
            $fields[] = "Currency: {$currency}";
            $prompt .= "\n\nIMPORTANT: Write the description in {$currency} currency using its symbol.";
        }

        if (!empty($fields)) {
            $prompt .= "\n" . implode("\n", $fields);
        }

        // Strong constraints AFTER data
        $prompt .= "\n\nInstructions:";
        $prompt .= "\n- Write in an engaging and professional tone";
        $prompt .= "\n- Highlight features and location benefits";
        $prompt .= "\n- Text only (no bullets, no emojis)";
        $prompt .= "\n- DO NOT assume or generate missing details";

        if (!empty($data['price'])) {
            $currency = $data['currency_iso_code'] ?? CachingService::getSystemSettings('currency_iso_code');
            $prompt .= "\n- MUST mention the price {$data['price']} naturally within the description using the {$currency} currency symbol";
        } else {
            $prompt .= "\n- DO NOT include any price since none was provided";
        }

        // Language
        if (!empty($data['language_name']) || !empty($data['language_code'])) {
            $language = $data['language_name'] ?? $data['language_code'];
            $prompt .= "\n- The entire description must be in {$language}";
        }

        return $prompt;
    }

    /**
     * Build SEO meta prompt
     */
    public function buildMetaPrompt(array $data): string
    {
        $prompt = "You are an SEO assistant. Based on the following item listing data, generate SEO meta details.\n\n";

        if (!empty($data['language_name']) || !empty($data['language_code'])) {
            $language = $data['language_name'] ?? $data['language_code'];
            $prompt .= "IMPORTANT: Write the meta details in {$language} language.\n\n";
        }

        $prompt .= "Title: " . ($data['title'] ?? 'N/A') . "\n";
        
        if (!empty($data['category_name'])) $prompt .= "Category: {$data['category_name']}\n";
        if (!empty($data['location'])) $prompt .= "Location: {$data['location']}\n";
        if (!empty($data['city'])) $prompt .= "City: {$data['city']}\n";
        if (!empty($data['state'])) $prompt .= "State: {$data['state']}\n";
        if (!empty($data['country'])) $prompt .= "Country: {$data['country']}\n";
        if (!empty($data['price'])) $prompt .= "Price: {$data['price']}\n";
        if (!empty($data['currency_iso_code'])) {
            $currency = $data['currency_iso_code'];
            $prompt .= "\n\nIMPORTANT: Write the meta details in {$currency} currency using its symbol.";
        }else{
            $currency = CachingService::getSystemSettings('currency_iso_code');
            $prompt .= "\n\nIMPORTANT: Write the meta details in {$currency} currency using its symbol.";
        }

        $prompt .= "\nReturn ONLY a valid JSON object with this exact structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"meta_title\": \"...\",\n";
        $prompt .= "  \"meta_description\": \"...\",\n";
        $prompt .= "  \"meta_keywords\": \"...\"\n";
        $prompt .= "}\n\n";
        $prompt .= "Rules:\n";
        $prompt .= "- meta_title: 50-60 characters, include location and key feature, Generate only when title is present\n";
        $prompt .= "- meta_description: 150-160 characters, compelling and clear, Generate only when description is present\n";
        $prompt .= "- meta_keywords: 10-15 comma-separated keywords, Generate only when title and description are present\n";
        $prompt .= "- Do NOT add any explanation, markdown, or extra text. JSON ONLY.";

        // $cacheKey = "gemini_meta_" . md5($prompt) . $this->getLanguageSuffix($data);
        // // Cache for 24 hours
        // Cache::store('gemini')->put($cacheKey, $prompt, 86400);

        return $prompt;
    }

    private function extractText(array $data): string
    {
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    private function cleanResponse(string $content): string
    {
        $content = trim($content);
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        return trim($content, " \t\n\r\0\x0B\"'");
    }

    private function parseMetaFromText(string $content): array
    {
        if (preg_match('/\{[^}]+\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) {
                return [
                    'meta_title' => $json['meta_title'] ?? '',
                    'meta_description' => $json['meta_description'] ?? '',
                    'meta_keywords' => $json['meta_keywords'] ?? '',
                ];
            }
        }

        $meta = ['meta_title' => '', 'meta_description' => '', 'meta_keywords' => ''];
        foreach (explode("\n", $content) as $line) {
            if (stripos($line, 'title') !== false) {
                $meta['meta_title'] = trim(str_replace(['Title:', 'Meta Title:'], '', $line));
            } elseif (stripos($line, 'description') !== false) {
                $meta['meta_description'] = trim(str_replace(['Description:', 'Meta Description:'], '', $line));
            } elseif (stripos($line, 'keyword') !== false) {
                $meta['meta_keywords'] = trim(str_replace(['Keywords:', 'Meta Keywords:'], '', $line));
            }
        }

        return $meta;
    }

    private function getLanguageSuffix(array $data): string
    {
        if (!empty($data['language_code'])) {
            return '_' . $data['language_code'];
        }
        if (!empty($data['language_name'])) {
            return '_' . md5($data['language_name']);
        }
        return '';
    }

    private function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}
