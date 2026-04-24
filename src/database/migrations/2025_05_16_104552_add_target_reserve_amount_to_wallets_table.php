<?php // Убедитесь, что php открывающий тег есть в самом начале

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Wallet; // Для доступа к константе

class AddTargetReserveAmountToWalletsTable extends Migration // Убрано 'public'
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->integer('target_reserve_amount')
                  ->default(Wallet::RESERVE_BALANCE) 
                  ->after('reserve_balance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('target_reserve_amount');
        });
    }
}