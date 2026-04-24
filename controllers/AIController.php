<?php
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/helpers.php';

class AIController {
    private Sale    $sale;
    private Product $product;
    private Message $message;

    public function __construct() {
        $this->sale    = new Sale();
        $this->product = new Product();
        $this->message = new Message();
    }

    /**
     * POST /api/ai/reply
     * Generates a professional AI reply to a customer message.
     * Optionally integrates with OpenAI API if OPENAI_API_KEY is set.
     */
    public function reply(): void {
        AuthMiddleware::handle();
        $data = getRequestBody();
        $err  = requireFields($data, ['customer_id', 'message']);
        if ($err) jsonResponse(422, ['error' => $err]);

        $customerId = (int)$data['customer_id'];
        $userMsg    = sanitize($data['message']);

        $reply = $this->generateReply($userMsg);

        $id = $this->message->create($customerId, $userMsg, $reply);
        jsonResponse(200, ['message_id' => $id, 'reply' => $reply]);
    }

    /**
     * GET /api/ai/insights
     * Returns top-selling products, low-stock alerts, and total revenue.
     */
    public function insights(): void {
        AuthMiddleware::handle();
        $topSelling = $this->product->getTopSelling(5);
        $lowStock   = $this->product->getLowStock(5);
        $revenue    = $this->sale->getTotalRevenue();

        jsonResponse(200, [
            'top_selling_products' => $topSelling,
            'low_stock_products'   => $lowStock,
            'total_revenue'        => $revenue,
        ]);
    }

    /**
     * GET /api/ai/report
     * Returns a readable daily business summary.
     */
    public function report(): void {
        AuthMiddleware::handle();
        $todayRevenue = $this->sale->getTodayRevenue();
        $todayCount   = $this->sale->getTodayCount();
        $topSelling   = $this->product->getTopSelling(3);
        $lowStock     = $this->product->getLowStock(5);

        $topNames = array_map(fn($p) => $p['name'] . " ({$p['total_sold']} sold)", $topSelling);
        $lowNames = array_map(fn($p) => $p['name'] . " (stock: {$p['stock_quantity']})", $lowStock);

        $summary = "Daily Business Report — " . date('Y-m-d') . "\n";
        $summary .= "Today's Sales: $todayCount transactions totaling $" . number_format($todayRevenue, 2) . ".\n";
        $summary .= count($topNames) > 0
            ? "Top Products: " . implode(', ', $topNames) . ".\n"
            : "No sales recorded yet.\n";
        $summary .= count($lowNames) > 0
            ? "Low Stock Alert: " . implode(', ', $lowNames) . ".\n"
            : "All products are well-stocked.\n";

        jsonResponse(200, ['report' => $summary, 'date' => date('Y-m-d')]);
    }

    // -----------------------------------------------------------------------
    // Internal: generate a reply via OpenAI or fallback to rule-based logic
    // -----------------------------------------------------------------------
    private function generateReply(string $message): string {
        $apiKey = getenv('OPENAI_API_KEY');
        if ($apiKey) {
            return $this->callOpenAI($message, $apiKey);
        }
        return $this->ruleBasedReply($message);
    }

    private function callOpenAI(string $message, string $apiKey): string {
        $payload = json_encode([
            'model'    => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional small business customer support assistant. Reply politely and helpfully.'],
                ['role' => 'user',   'content' => $message],
            ],
            'max_tokens' => 200,
        ]);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($response, true);
        return $decoded['choices'][0]['message']['content'] ?? $this->ruleBasedReply($message);
    }

    private function ruleBasedReply(string $message): string {
        $msg = strtolower($message);
        if (str_contains($msg, 'price') || str_contains($msg, 'cost')) {
            return "Thank you for your inquiry. Please check our product catalog for the latest pricing, or contact us directly for a custom quote.";
        }
        if (str_contains($msg, 'order') || str_contains($msg, 'purchase')) {
            return "Thank you for your order interest. Our team will process your request and get back to you shortly.";
        }
        if (str_contains($msg, 'refund') || str_contains($msg, 'return')) {
            return "We're sorry to hear about your experience. Please provide your order details and we'll resolve this as quickly as possible.";
        }
        if (str_contains($msg, 'delivery') || str_contains($msg, 'shipping')) {
            return "Your delivery is important to us. Please share your order ID and we'll provide a real-time update.";
        }
        return "Thank you for reaching out. A member of our team will respond to your message within 24 hours.";
    }
}
