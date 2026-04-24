<?php
namespace App\Console\Commands;

use App\DTO\Order\CreateOrderDTO;
use App\Services\ProxyFi\ProxyFiClientFactory;
use App\Services\ProxyFi\ProxyFiResponseTransformer;
use App\Services\ProxyFi\ProxyFiService;
use Exception;
use Illuminate\Console\Command;

class ProxyFiTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ProxyFi:process {json}';


    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $data = json_decode($this->argument('json'), true);

        try {
            $req  = CreateOrderDTO::makeFromRequest($data);
            $service = new ProxyFiService(new ProxyFiClientFactory(), new ProxyFiResponseTransformer());
            $result = $service->processOrderDto($req);
            $this->line("Request processed successfully.\n". json_encode($result));
        } catch (Exception $exception) {
            $this->error("Error: " . $exception->getMessage());
            return 1;
        }

        return 0;
    }
}
