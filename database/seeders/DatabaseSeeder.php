<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderProduct;
use App\Models\WorkOrderService;
use App\Models\WorkOrderStatusHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create(['name'=>'Kauã Nogueira','email'=>'admin@apollo.com.br','password'=>Hash::make('password'),'role'=>'admin']);
        $johnUser = User::create(['name'=>'João Santos','email'=>'joao@apollo.com.br','password'=>Hash::make('password'),'role'=>'technician']);
        $pedroUser = User::create(['name'=>'Pedro Lima','email'=>'pedro@apollo.com.br','password'=>Hash::make('password'),'role'=>'technician']);
        $john = Employee::create(['user_id'=>$johnUser->id,'phone'=>'(98) 99123-4500','specialty'=>'Manutenção e limpeza','hired_at'=>'2024-02-05']);
        $pedro = Employee::create(['user_id'=>$pedroUser->id,'phone'=>'(98) 98842-1120','specialty'=>'Instalação','hired_at'=>'2025-01-13']);

        $serviceData = [['Limpeza de Split','Limpeza',150,60],['Visita técnica','Diagnóstico',100,45],['Manutenção preventiva','Manutenção',220,90],['Troca de capacitor','Manutenção corretiva',120,45],['Instalação 12.000 BTUs','Instalação',650,240],['Carga de gás','Manutenção corretiva',280,90]];
        $services = collect($serviceData)->map(fn($s)=>Service::create(['name'=>$s[0],'category'=>$s[1],'default_price'=>$s[2],'estimated_cost'=>$s[2]*.28,'average_minutes'=>$s[3]]));
        $products = collect([['Capacitor 35µF','Elétrica','un',28,55,8,3],['Gás R410A','Refrigeração','kg',92,180,5,2],['Tubo de cobre 1/4','Tubulação','m',22,42,18,10],['Isolamento térmico','Tubulação','m',7,16,7,10]])->map(fn($p)=>Product::create(['name'=>$p[0],'category'=>$p[1],'unit'=>$p[2],'cost_price'=>$p[3],'sale_price'=>$p[4],'stock'=>$p[5],'minimum_stock'=>$p[6]]));

        $customers = collect([
            ['Carlos Silva','(98) 98811-2233','carlos@email.com','Residência','Rua das Acácias','120','Cohama','São Luís','MA','Sala','LG','Split',12000],
            ['Empresa ABC','(98) 3227-8080','contato@empresaabc.com','Matriz','Av. dos Holandeses','8','Calhau','São Luís','MA','Recepção','Samsung','Cassete',24000],
            ['Maria Ferreira','(98) 99102-4455','maria@email.com','Residência','Rua do Aririzal','340','Turu','São Luís','MA','Quarto','Gree','Split',9000],
            ['Clínica Sorriso','(98) 3235-6677','financeiro@clinicasorriso.com','Clínica','Av. Daniel de La Touche','15','Cohajap','São Luís','MA','Consultório 2','Daikin','Split',18000],
        ])->map(function($c){$customer=Customer::create(['name'=>$c[0],'phone'=>$c[1],'whatsapp'=>$c[1],'email'=>$c[2]]);$address=$customer->addresses()->create(['label'=>$c[3],'street'=>$c[4],'number'=>$c[5],'district'=>$c[6],'city'=>$c[7],'state'=>$c[8]]);$equipment=Equipment::create(['customer_id'=>$customer->id,'address_id'=>$address->id,'location'=>$c[9],'brand'=>$c[10],'type'=>$c[11],'btus'=>$c[12],'next_maintenance_at'=>now()->addDays(rand(8,50))->toDateString()]);return compact('customer','address','equipment');});

        $schedule = [[0,8,0,'Limpeza','completed',$john],[1,10,30,'Manutenção','in_service',$john],[2,14,0,'Instalação','scheduled',$pedro],[3,16,0,'Visita técnica','confirmed',$pedro]];
        foreach($schedule as $i=>$row){[$customerIndex,$hour,$minute,$type,$status,$tech]=$row;$group=$customers[$customerIndex];$appointment=Appointment::create(['customer_id'=>$group['customer']->id,'address_id'=>$group['address']->id,'equipment_id'=>$group['equipment']->id,'technician_id'=>$tech->id,'scheduled_at'=>now()->startOfDay()->setTime($hour,$minute),'duration_minutes'=>$type==='Instalação'?240:90,'type'=>$type,'status'=>$status,'notes'=>'Atendimento agendado pela central.']);$order=WorkOrder::create(['number'=>'OS-'.now()->format('Ym').'-'.str_pad((string)($i+1),4,'0',STR_PAD_LEFT),'customer_id'=>$group['customer']->id,'address_id'=>$group['address']->id,'equipment_id'=>$group['equipment']->id,'technician_id'=>$tech->id,'appointment_id'=>$appointment->id,'scheduled_at'=>$appointment->scheduled_at,'problem_reported'=>$i===1?'Equipamento não está refrigerando corretamente.':'Manutenção solicitada pelo cliente.','diagnosis'=>$status==='completed'?'Evaporadora com acúmulo de sujeira e filtros saturados.':null,'solution'=>$status==='completed'?'Higienização completa e teste de funcionamento.':null,'status'=>$status]);$service=$services[$i%$services->count()];WorkOrderService::create(['work_order_id'=>$order->id,'service_id'=>$service->id,'name'=>$service->name,'quantity'=>1,'unit_price'=>$service->default_price,'discount'=>0,'total'=>$service->default_price]);if($i===1){$product=$products[0];WorkOrderProduct::create(['work_order_id'=>$order->id,'product_id'=>$product->id,'name'=>$product->name,'quantity'=>1,'unit_price'=>$product->sale_price,'discount'=>0,'total'=>$product->sale_price]);}$total=(float)$order->services()->sum('total')+(float)$order->products()->sum('total');$order->update(['subtotal'=>$total,'total'=>$total,'started_at'=>in_array($status,['in_service','completed'])?$appointment->scheduled_at->copy()->addMinutes(8):null,'finished_at'=>$status==='completed'?$appointment->scheduled_at->copy()->addMinutes(76):null]);$events=['scheduled'];if(in_array($status,['in_service','completed']))$events=array_merge($events,['traveling','arrived','in_service']);if($status==='completed')$events[]='completed';foreach($events as $n=>$event)WorkOrderStatusHistory::create(['work_order_id'=>$order->id,'user_id'=>$tech->user_id,'from_status'=>$n?$events[$n-1]:null,'to_status'=>$event,'occurred_at'=>$appointment->scheduled_at->copy()->addMinutes($n*12)]);if($status==='completed')Payment::create(['work_order_id'=>$order->id,'amount'=>$total,'method'=>'pix','paid_at'=>$order->finished_at,'created_by'=>$admin->id]);}
    }
}

