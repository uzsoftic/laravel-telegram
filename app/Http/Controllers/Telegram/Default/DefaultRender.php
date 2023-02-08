<?php

namespace App\Http\Controllers\Service\Telegram\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\SubSubCategory;
use App\Models\TelegramUser;
use App\Models\Order;
use Carbon\Carbon;

use App\Http\Controllers\Service\Telegram\BotCommands;
use App\Http\Controllers\Service\Telegram\BotFunctions;
use App\Http\Controllers\Service\Telegram\Ecommerce\EcommerceString;

class EcommerceRender extends Controller
{

    public $functions;
    public $string;
    public $lang;

    public function __construct($language = 'ru'){
        $this->functions = new BotFunctions;
        $this->string = new EcommerceString;
        $this->lang = $language;
    }

    /////////////////////////////////////////////
    //////////////    LOGIN PAGE    /////////////
    /////////////////////////////////////////////

    public function showSelectLanguage(){
        $text = 'Здравствуйте! Давайте для начала выберем язык обслуживания!

Keling, avvaliga xizmat ko’rsatish tilini tanlab olaylik.

Hi! Let\'s first we choose language of serving!

Если у вас есть вопросы, вы можете связаться с нами по телеграмме: @wwwopenshopuz

Наш колл-центр: (71) 200 66 60 и (78) 148 66 60';

        return $text;
    }
    public function keySelectLanguage(){
        return [
            [['text' => '🇺🇿 O`zbekcha', 'callback_data' => 'cb_lang_set_uz']],
            [['text' => '🇷🇺 Русский', 'callback_data' => 'cb_lang_set_ru']],
            [['text' => '🇺🇸 English', 'callback_data' => 'cb_lang_set_en']],
        ];
    }

    public function LanguageSelected($code){
        if($code == 'uz')
            $text = 'O`zbek tili tanlandi';
        elseif($code == 'ru')
            $text = 'Вы выбрали русский язык';
        elseif($code == 'en')
            $text = 'English language selected';
        return $text;
    }

    public function keySharePhoneNumber(){
        return [
            [['text' => $this->text('button_share_my_number'), 'request_contact' => true]],
        ];
    }
    public function keyResendVerifyCode(){
        return [
            [['text' => $this->text('button_resend_verify_code'), 'callback_data' => 'cb_resendsmscode']],
        ];
    }

    /////////////////////////////////////////////
    //////////////    BOT PAGES    //////////////
    /////////////////////////////////////////////

    public function keyboardHome(){
        return [
            [['text' => $this->text('button_all_catalog')]],
            [['text' => $this->text('button_cart')],['text' => $this->text('button_myprofile')]],
            [['text' => $this->text('button_infobot')],['text' => $this->text('button_rules')]],
            [['text' => $this->text('button_support')],['text' => $this->text('button_callcenter')]],
            [['text' => $this->text('button_social_network')]],
            //[['text' => $this->text('button_pricelist')]],
            [['text' => $this->text('button_back_to_main')]],
        ];
    }
    public function inlineHome(){
        return [
            [['text' => $this->text('button_all_catalog'), 'callback_data' => 'cb_categories']],
            [['text' => $this->text('button_cart'), 'callback_data' => 'cb_cart'],['text' => $this->text('button_orders'), 'callback_data' => 'cb_orders']],
            [['text' => $this->text('button_myprofile'), 'callback_data' => 'cb_profile']],
            [['text' => $this->text('button_infobot'), 'callback_data' => 'cb_about'],['text' => $this->text('button_rules'), 'callback_data' => 'cb_rules']],
            [['text' => $this->text('button_support'), 'callback_data' => 'cb_support'],['text' => $this->text('button_callcenter'), 'callback_data' => 'cb_callcenter']],
            [['text' => $this->text('button_settings'), 'callback_data' => 'cb_settings']],
            [['text' => $this->text('button_social_network'), 'url' => 'https://t.me/openshop_uz']],
            //[['text' => $this->text('button_pricelist'), 'callback_data' => 'cb_pricelist']],
            [['text' => $this->text('button_share_friends'), 'switch_inline_query' => '']],
            [['text' => $this->text('button_search'), 'switch_inline_query_current_chat' => '']],
        ];
    }

    public function showOrders($chat_id){
        $orders = Order::where('direction', 'telegram')->where('deleted_status', false)->where('guest_id', $chat_id)->orderBy('created_at', 'desc')->limit(20)->get();

        $text = "<b>".$this->text('button_orders')."</b>\n\n";

        if(count($orders) > 0){ //
            foreach($orders as $order){
                $details = json_decode($order->shipping_address);
                $text .= "#".$order->id." - ".$details->name." - ".$this->functions->formatPrice($order->grand_total, $this->text('uzs'))."\n<i>".$order->created_at->format('d.M.Y H:i')."</i> - <b>".$this->text('order_'.json_decode($order->orderDetails[0])->delivery_status)."</b>\n";
                //sendTelegram('me', json_encode($order));
            }
        }else{
            $text .= $this->text('string_empty_orders');
        }

        return $text;
    }
    public function keyOrders($chat_id){

        $orders = Order::where('direction', 'telegram')->where('deleted_status', false)->where('guest_id', $chat_id)->orderBy('created_at', 'desc')->limit(20)->get();
        $construct = array();

        foreach ($orders as $order){
            $construct[] = array(['text' => "#".$order->id." - 💳 ".$this->functions->formatPrice($order->grand_total, $this->text('uzs')), 'callback_data' => 'cb_orderbyid_'.$order->id]);
        }

        $construct[] = array(['text' => $this->text('button_back'), 'callback_data' => 'cb_homepage']);
        return $construct;

    }

    public function showOrder($id){

        $order = Order::where('id', $id)->first();
        $information = json_decode($order->shipping_address);

        $proccess = ''; //count($details);
        foreach($order->orderDetails as $key => $detail){
            $product = Product::where('id', $detail->product_id)->first();
            $math_id = $key + 1;
            $math_price = $detail->price * $detail->quantity;
            $proccess .= $math_id.". ".$product->name." <b>x".$detail->quantity." ".$this->text('pcs')."</b>\n➖ ".$this->formatPrice($detail->price)." <b>x".$detail->quantity." = ".$this->formatPrice($math_price)."</b>\n";
        }

        $client = $this->text('string_user_id').'<b>#'.$order->guest_id.'</b>';

        if($order->payment_type == 'cash_on_delivery'){ $order->payment_type = $this->text('string_cash_on_delivery');
        }else{ $order->payment_type = $this->text('string_terminal'); }

        if($order->payment_status == 'paid'){ $order->payment_status = $this->text('string_paid');
        }else{ $order->payment_status = $this->text('string_unpaid'); }


$text = $this->text('string_order_id').'<b>#'.$order->id.'</b>

'.$client.'
'.$this->text('string_order_name').'<b>'.$information->name.'</b>
'.$this->text('string_order_city').'<b>'.$information->city.'</b>
'.$this->text('string_order_address').'<b>'.$information->address.'</b>
'.$this->text('string_order_phone').$information->phone.'

'.$proccess.'
'.$this->text('string_order_payment_method').'<b>'.$order->payment_type.'</b>
'.$this->text('string_order_payment_status').'<b>'.$order->payment_status.'</b>
'.$this->text('string_total_amount').'<b>'.$this->formatPrice($order->grand_total).'</b>

'.$this->text('string_order_created_at').'<b>'.$order->created_at->format('d.m.Y H:i').'</b>';

return $text;

    }

    public function keyOrder(){
        return [
            [
                ['text' => $this->text('button_myprofile'), 'callback_data' => 'cb_profile'],
                ['text' => $this->text('button_back_to_main'), 'callback_data' => 'cb_homepage'],
            ],
            [['text' => $this->text('button_back'), 'callback_data' => 'cb_orders']],
        ];
    }

    public function showCart($chat_id){
        $telegram_user = TelegramUser::where('chat_id', $chat_id)->first();
        $array = json_decode($telegram_user->cart, true);

        $summa = '0';
        $delivery = '0';

        if(isset($array)){
            $proccess = '';
            foreach($array as $key => $data){
                $product = Product::where('id', $data['id'])->first();
                $math_id = $key + 1;
                    $math_price = home_discounted_base_price($product->id, 1) * $data['count'];
                    $summa += $math_price;
                $proccess .= $math_id.". ".$product->name." <b>x".$data['count']." ".$this->text('pcs')."</b>\n➖ ".home_discounted_base_price($product->id, 'bot')." <b>x".$data['count']." = ".number_format($math_price)." ".$this->text('uzs')."</b>\n";
            }
            if($summa < '1000000'){
                $delivery = '25000';
            }else{
                $delivery = '0';
            }
        }else{
            $proccess = $this->text('string_your_cart_empty');
        }

$text = "<b>".$this->text('string_cart')."</b>\n\n".$proccess."

".$this->text('string_products').number_format($summa)." ".$this->text('uzs')."
".$this->text('string_delivery').number_format($delivery)." ".$this->text('uzs')."

<b>".$this->text('string_total_amount').number_format($summa + $delivery)." ".$this->text('uzs')."</b>";
        return $text;
    }
    public function keyCart($chat_id){

        $telegram_user = TelegramUser::where('chat_id', $chat_id)->first();
        $array = json_decode($telegram_user->cart, true);

        if(isset($array) && count($array) > 0){
            return [
                [['text' => $this->text('button_cancel'), 'callback_data' => 'cb_clearcart'],
                 ['text' => $this->text('button_refresh'), 'callback_data' => 'cb_cart'],
                 ['text' => $this->text('button_confirm'), 'callback_data' => 'cb_checkout']],

                [['text' => $this->text('button_back'), 'callback_data' => 'cb_homepage']],
            ];
        }else{
            return [
                [['text' => $this->text('button_cancel'), 'callback_data' => 'cb_clearcart'],
                 ['text' => $this->text('button_refresh'), 'callback_data' => 'cb_cart']],

                [['text' => $this->text('button_back'), 'callback_data' => 'cb_homepage']],
            ];
        }
    }

    public function showCheckout($chat_id){
        $telegram_user = TelegramUser::where('chat_id', $chat_id)->first();
        $array = json_decode($telegram_user->cart, true);

        $summa = '0';
        $delivery = '0';
        $discount = '0';

        if(isset($array)){
            $proccess = '';
            foreach($array as $key => $data){
                $product = Product::where('id', $data['id'])->first();
                $math_id = $key + 1;
                    $math_price = home_discounted_base_price($product->id, 1) * $data['count'];
                    $summa += $math_price;
                $proccess .= $math_id.". ".$product->name." <b>x".$data['count']." ".$this->text('pcs')."</b>\n➖ ".home_discounted_base_price($product->id, 'bot')." <b>x".$data['count']." = ".number_format($math_price)." ".$this->text('uzs')."</b>\n";
            }
            if($summa < '1000000'){
                $delivery = '25000';
            }else{
                $delivery = '0';
            }
        }else{
            $proccess = $this->text('string_your_cart_empty');
        }

        if(!empty($this->getUserInfo('phone', $chat_id, 0, NULL))){
            $phone = "+".$this->getUserInfo('phone', $chat_id, 0);
        }else{
            $phone = $this->text('empty');
        }

$checkout = "\n\n<b>".$this->text('string_checkout_account')."</b>

".$this->text('string_order_name')."<b>".$this->getUserInfo('name', $chat_id)."</b>
".$this->text('string_order_phone_number').$phone."
".$this->text('string_order_email').$this->getUserInfo('email', $chat_id)."


<b>".$this->text('string_checkout_delivery')."</b>

".$this->text('string_order_region')."<b>".$this->getUserInfo('region', $chat_id)."</b>
".$this->text('string_order_address')."<b>".$this->getUserInfo('address', $chat_id, 1, 'string_empty_address')."</b>


<b>".$this->text('string_checkout_payment')."</b>
".$this->text('string_checkout_payment_method')."<b>".$this->getUserInfo('payment', $chat_id)."</b>";

// <b>Coupon: </b><i>not used</i>
// Comment: this is simple comment for order

$text = "<b>".$this->text('string_cart')."</b>\n".$proccess.$checkout."

".$this->text('string_products').number_format($summa)." ".$this->text('uzs')."
".$this->text('string_delivery').number_format($delivery)." ".$this->text('uzs')."
".$this->text('string_discount').number_format($discount)." ".$this->text('uzs')."

<b>".$this->text('string_total_amount').number_format($summa + $delivery)." ".$this->text('uzs')."</b>";
        return $text;
    }
    public function keyCheckout($chat_id){

        $telegram_user = TelegramUser::where('chat_id', $chat_id)->first();
        $array = json_decode($telegram_user->cart, true);

        return [
           /* [
                ['text' => $this->text('button_change_order'), 'callback_data' => 'cb_checkout_account_show_listing'],
            ],
            [
                ['text' => $this->text('button_change_delivery'), 'callback_data' => 'cb_checkout_delivery_show_listing'],
                ['text' => $this->text('button_change_payment'), 'callback_data' => 'cb_checkout_payment_show_listing'],
            ],
            [
                ['text' => $this->text('button_use_coupon'), 'callback_data' => 'cb_checkout_use_coupon'],
            ],*/
            [
                ['text' => $this->text('button_change_name'), 'callback_data' => 'cb_checkout_account_change_name'],
            ],
            [
                ['text' => $this->text('button_change_phone'), 'callback_data' => 'cb_checkout_account_change_phone'],
                ['text' => $this->text('button_change_address'), 'callback_data' => 'cb_checkout_delivery_change_address']
            ],
            [
                ['text' => $this->text('button_back_to_main'), 'callback_data' => 'cb_homepage'],
                ['text' => $this->text('button_complate'), 'callback_data' => 'cb_createorder'],
            ],
            [
                ['text' => $this->text('button_back'), 'callback_data' => 'cb_cart'],
            ],
        ];
    }

    public function showAccountDetailsCheckout($chat_id){
        return "<b>".$this->text('string_checkout_account')."</b>

".$this->text('string_order_name').$this->getUserInfo('name', $chat_id)."
".$this->text('string_order_phone_number').$this->getUserInfo('phone', $chat_id)."
".$this->text('string_order_email').$this->getUserInfo('email', $chat_id);
    }
    public function keyAccountDetailsCheckout(){

        return [
            [
                ['text' => $this->text('button_change_name'), 'callback_data' => 'cb_checkout_account_change_name'],
            ],
            [
                ['text' => $this->text('button_change_phone'), 'callback_data' => 'cb_checkout_account_change_phone'],
                ['text' => $this->text('button_change_mail'), 'callback_data' => 'cb_checkout_account_change_email'],
            ],
            [
                ['text' => $this->text('button_back'), 'callback_data' => 'cb_checkout'],
            ],
        ];
    }

    public function showDeliveryDetailsCheckout($chat_id){
        return "<b>".$this->text('string_checkout_delivery')."</b>

".$this->text('string_order_region')."<b>".$this->getUserInfo('region', $chat_id)."</b>
".$this->text('string_order_address')."<b>".$this->getUserInfo('address', $chat_id, 'string_empty_address')."</b>";

    }
    public function keyDeliveryDetailsCheckout(){
        return [
            [['text' => $this->text('button_edit_delivery_region'), 'callback_data' => 'cb_checkout_delivery_change_region']],
            [['text' => $this->text('button_edit_delivery_address'), 'callback_data' => 'cb_checkout_delivery_change_address']],
            //[['text' => $this->text('button_edit_delivery_location'), 'callback_data' => 'cb_checkout_delivery_change_location']],
            [['text' => $this->text('button_back'), 'callback_data' => 'cb_checkout']],
        ];
    }

    public function showPaymentDetailsCheckout($chat_id){
        return "<b>".$this->text('string_checkout_payment')."</b>

<b>".$this->text('string_checkout_payment_method')."</b><i>".$this->getUserInfo('payment', $chat_id)."</i>";
    }

    public function keyPaymentDetailsCheckout(){

        return [
            [
                ['text' => $this->text('click'), 'callback_data' => 'cb_checkout_payment_update_click'],
                ['text' => $this->text('cash'), 'callback_data' => 'cb_checkout_payment_update_cash'],
            ],
            [
                ['text' => $this->text('uzcard'), 'callback_data' => 'cb_checkout_payment_update_uzcard'],
                ['text' => $this->text('humo'), 'callback_data' => 'cb_checkout_payment_update_humo'],
            ],
            [['text' => $this->text('button_back'), 'callback_data' => 'cb_checkout']],
        ];
    }

    public function showProfile($chat_id){
        $telegram_user = TelegramUser::where('chat_id', '=', $chat_id)->first();
        $information = json_decode($telegram_user->information);
        $text = $this->text('button_myprofile')."\n\n".$this->text('string_your_id').$chat_id;
        //AUTH USER BLOCK
        /*if(!empty($telegram_user->user_id)){
            $user = User::where('id', $telegram_user->user_id)->first();
            $text .= "\n".$this->text('string_wallet')."<b>".$user->balance."</b>
                    \n".$this->text('string_bonus_ball')."<b>0</b>";
        }*/

        $text .= "\n".$this->text('string_your_name_surname')."<b>".$this->getUserInfo('name', $chat_id)."</b>
".$this->text('string_your_phone_number')."<b>".$this->getUserInfo('number', $chat_id)."</b>
".$this->text('string_your_email')."<b>".$this->getUserInfo('email', $chat_id, 'string_empty_email')."</b>
".$this->text('string_your_address')."<b>".$this->getUserInfo('address', $chat_id, 'string_empty_address')."</b>
".$this->text('string_registered').$telegram_user->created_at;

        return $text;
    }
    public function keyProfile(){
        return [
            [['text' => $this->text('button_change_name'), 'callback_data' => 'cb_edit_name'],['text' =>$this->text('button_change_address'), 'callback_data' => 'cb_edit_address']],
            [['text' => $this->text('button_change_mail'), 'callback_data' => 'cb_edit_email'],['text' => $this->text('button_change_phone'), 'callback_data' => 'cb_edit_phone']],
            [['text' => $this->text('button_cart'), 'callback_data' => 'cb_cart']],
            [['text' => $this->text('button_back'), 'callback_data' => 'cb_homepage']],
        ];
    }

    public function showSettings(){
        //$text = "<b>".$this->text('string_language');

        $text = "<b>".$this->text('string_settings')."</b>\n\n
".$this->text('string_language')."<b>".$this->text("language_".$this->lang)."</b>
".$this->text('string_notificaiton')."<b>".$this->text('button_enabled')."</b>
".$this->text('string_pair_website')."<b>".$this->text('button_not_connected')."</b>\n
".$this->text('string_info_change_language');

        return $text;
    }
    public function keySettings(){
        return [
            [
                ['text' => $this->text('language_uz'), 'callback_data' => 'cb_lang_set_uz'],
                //['text' => $this->text('language_oz'), 'callback_data' => 'cb_lang_set_oz'],
            ],
            /*[
                ['text' => $this->text('language_kq'), 'callback_data' => 'cb_lang_set_kq'],
                ['text' => $this->text('language_tr'), 'callback_data' => 'cb_lang_set_tr'],
            ],*/
            [
                ['text' => $this->text('language_ru'), 'callback_data' => 'cb_lang_set_ru'],
                ['text' => $this->text('language_en'), 'callback_data' => 'cb_lang_set_en'],
            ],
            [
                //['text' => $this->text('language_ua'), 'callback_data' => 'cb_lang_set_ua'],
                //['text' => $this->text('language_kr'), 'callback_data' => 'cb_lang_set_kr'],
            ],
            [
                //['text' => $this->text('button_notificaiton'), 'callback_data' => 'cb_categories'],
                //['text' => $this->text('button_pair_website'), 'callback_data' => 'cb_categories']
            ],
            [
                ['text' => $this->text('button_back'), 'callback_data' => 'cb_homepage']
            ],
        ];
    }

    public function showAbout(){
        $text = "ℹ️Информация о боте

Интернет магазин Openshop.uz - Самый выгодный интернет магазин с доставкой по всему Узбекистану!

В нашем интернет магазине вы можете купить товары по доступной вам цене ! Большой ассортимент , Быстрая доставка , Отличный сервис , Характеристики и описание товара ! Всё это имеется в нашем интернет магазине Openshop.uz ! Заказать легко и просто , наши операторы свяжутся с вами и подробно объяснят ! Доставка осуществляется по всему Узбекистану !

Контактная информация
Адрес:
Республика Узбекистан, город Ташкент, Мирзо-Улугбекский район,
Буз  сув 2 - 69
Телефон для справок :
+998 (71) 200 66 60
+998 (78) 148 66 60
Эл. почта:
info@openshop.uz";
        return $text;
    }

    public function showPolicy(){
        $text = "📃 Все правило:

1.  Общее положение.
2.  Оформление и сроки выполнения заказа.
3.  Оплата заказа.
4.  Прочие условия.
5.  Условия доставки предзаказанных товаров из за рубежа.
6.  OPENSHOP.UZ оставляет за собой право для изменений условий купли и продаж, товаров и услуг без обязательств и предупреждений!

ВНИМАНИЕ! Данные условия действуют только если продавцом товара является OOO \"OPEN SHOP\", если продавцом выступает другое юридическое лицо, то доставка товаров осуществляется по условиям продавца. Ознакомится с условиями продажи и доставки другого продавца вы можете на его странице в магазине.

Подробно читайте правила в нашем интернет магазине в разделе Условия продажи и доставки !

https://openshop.uz/sellerpolicy";
        return $text;
    }

    public function showSupport(){
        $text = "🧯 Служба поддержки

Здесь вы найдете ответы на самые часто задаваемые вопросы: о товарах на сайте и в магазинах, о доставке, об оплате, об акциях и в  специальных программах, о сервисе и о ремонте, а также о многом другом.

✅Как зарегистрировать товар?

✅Товары нет в магазине , однако срок доставки “Завтра”. Что это означает ?
✅Где можно посмотреть характеристика товара ?

✅Где найти инструкцию к товару ?

✅Почему на сайте сначала была одна цена , а потом она изменилась ?

✅Почему у товара раньше была кнопка “Добавить в корзину” , а теперь её нет?

✅Почему различаются сроки доставки товаров ?

✅Могу ли я заказать товар в интернет магазине другого города ?

✅На сайте не указаны телефоны магазинов , Как узнать о наличии товара ?

✅Не могу найти на сайте модель , хотя знаю , что она есть в магазине ?

✅Где найти инструкцию к товару ?

Связаться со службой поддержки OPENSHOP.UZ

Мы не оставим вас наедине с возникшей проблемой. Попробуйте найти решение в Справочном центре или задайте вопрос на форуме. Кроме того, наши сотрудники готовы помочь вам по телефону, в чате или по электронной почте.


На все вопросы вы можете найти ответы на нашем сайте в разделе Служба поддержки
https://openshop.uz/supportpolicy";
        return $text;
    }

        public function showContact(){
        $text = "<b>Связаться с нами</b>
📞Колл-центр

Связаться с нами

🔰Наши номера
(71) 200-66-60
(78) 148-66-60

🔰Мы в телеграмм
@openshop_uz

🔰Онлайн тех. поддержка
@wwwopenshopuz

🔰Если у возникнут вопросы, свяжитесь с нами и наши операторы помогут вам решить проблемы с подробными объяснениями!

⚙️Техподдержка работает с 9:00 до 21:00

🔰Вы можете позвонить в нашу службу поддержки
или написать на нашу электронную почту info@openshop.uz !

Интернет магазин : www.openshop.uz";
        return $text;
    }

    public function showChannel(){
        $text = 'Telegram: <a href="https://t.me/openshop_uz">LINK</a>

Instagram: [Link](https://instagram.com/openshop_uz)';
        return $text;
    }

    public function keyBack(){
        return [
            [['text' => $this->text('button_back'), 'callback_data' => 'cb_homepage']],
        ];
    }

    /////////////////////////////////////////////
    //////////////    ECOMMERCE    //////////////
    /////////////////////////////////////////////

    public function keySearchText($q){
        $categories = SubSubCategory::where('deleted', 0)->where('name', 'like', '%'.$q.'%')->limit(5)->get();
        $products =  filterProducts(Product::where('name', 'like', '%'.$q.'%')->select('id', 'name', 'slug'))->limit(10)->get();
        $construct = array();

        foreach($categories as $category)
            $construct[] = array(['text' => '🗂 '.$category->name.' - '.$category->subcategory->name, 'url' => 'https://openshop.uz/shop/subsubcategory/'.$category->slug]);
        //    $construct[] = array(['text' => $category->name, 'callback_data' => 'cb_list_'.$category->id]);
        foreach($products as $product)
            $construct[] = array(['text' => '🛍 '.$product->name, 'callback_data' => 'productbyid_'.$product->id.'_1']);

        // $bot->sendPhoto($chat_id, $render->showProductDetails($product_id), $render->showProductImage($product_id), $bot->makeInline($render->inlineShowProductByID($product_id, 1)));

        $construct[] = array(['text' => $this->text('button_back'), 'callback_data' => 'cb_homepage']);
        return $construct;
    }

    public function keyPriceList(){

        $categories = SubSubCategory::where('deleted', 0)->where('sub_category_id', '4')->select('id', 'name')->get();
        $construct = array();

        foreach($categories as $category)
            $construct[] = array(['text' => $category->name, 'callback_data' => 'cb_list_'.$category->id]);

        $construct[] = array(['text' => $this->text('button_back'), 'callback_data' => 'cb_homepage']);
        return $construct;
    }
        public function showPriceSend($id){
        $category = SubSubCategory::where('id', $id)->first();
        $products = Product::where('subsubcategory_id', $id)
            ->where('deleted', 0)
            ->where('moderated', 0)
            ->where('published', 1)
            ->where('current_stock', '>', 0)
            ->get()
            ->sortBy('purchase_price');

        $proccess = '';
        $i = 0;
        foreach($products as $product){
            $i++;
            //$proccess .= $i.'. '.$product->name.' - '.number_format($product->purchase_price).' сум
            $proccess .= $i.'. '.$product->name.' - '.home_discounted_price($product->id).'
';
            //$proccess .= $product->name.' - '.number_format($product->purchase_price).' сум<br>';
        }

$text = 'Интернет магазин OPENSHOP.UZ

Категория: '. $category->name.'

'.$proccess.'

'.now().'

#openshop #price';
        return $text;

    }

    public function keyPriceSend(){
        return [
            [['text' => $this->text('button_back'), 'callback_data' => 'cb_homepage']]
        ];
    }

    public function inlineCategories(){

        $categories = Category::where('status', 1)->where('featured', 1)->select('id', 'name')->get();
        $construct = array();

        foreach($categories as $category)
            $construct[] = array(['text' => '🗂 '.$this->functions->translate($category->name, $this->lang), 'callback_data' => 'cb_cat_'.$category->id]);
            //$construct[] = array(['text' => $category->name, 'callback_data' => 'cb_cat_'.$category->id]);

        $construct = array_merge($construct, [
            [
                ['text' => $this->text('button_cart'), 'callback_data' => 'cb_cart'],
                ['text' => $this->text('button_all_catalog'), 'callback_data' => 'cb_categories'],
                ['text' => $this->text('button_back_to_main'), 'callback_data' => 'cb_homepage']
            ],
            [['text' => $this->text('button_back'), 'callback_data' => 'cb_homepage']],
        ]);
        return $construct;
    }
    public function inlineCategoryById($cat){

        $categories = SubCategory::where('category_id', $cat)->select('id', 'name')->get();
        $construct = array();

        foreach($categories as $category)
            $construct[] = array(['text' => $this->functions->translate($category->name, $this->lang), 'callback_data' => 'cb_scat_'.$cat.'_'.$category->id]);
            //$construct[] = array(['text' => $category->name, 'callback_data' => 'cb_scat_'.$cat.'_'.$category->id]);

        $construct = array_merge($construct, [
            [
                ['text' => $this->text('button_cart'), 'callback_data' => 'cb_cart'],
                ['text' => $this->text('button_all_catalog'), 'callback_data' => 'cb_categories'],
                ['text' => $this->text('button_back_to_main'), 'callback_data' => 'cb_homepage']
            ],
            [['text' => $this->text('button_back'), 'callback_data' => 'cb_categories']],
        ]);
        return $construct;
    }
    public function inlineSubCategoryById($cat, $scat){

        $categories = SubSubCategory::where('deleted', 0)->where('sub_category_id', $scat)->select('id', 'name')->get();
        $construct = [];

        foreach($categories as $category)
            $construct[] = array(['text' => $this->functions->translate($category->name, $this->lang), 'callback_data' => 'cb_sscat_'.$cat.'_'.$scat.'_'.$category->id.'_1']);
            //$construct[] = array(['text' => $category->name, 'callback_data' => 'cb_sscat_'.$cat.'_'.$scat.'_'.$category->id.'_1']);

        $construct = array_merge($construct, [
            [
                ['text' => $this->text('button_cart'), 'callback_data' => 'cb_cart'],
                ['text' => $this->text('button_all_catalog'), 'callback_data' => 'cb_categories'],
                ['text' => $this->text('button_back_to_main'), 'callback_data' => 'cb_homepage']
            ],
            [['text' => $this->text('button_back'), 'callback_data' => 'cb_cat_'.$cat]],
        ]);
        return $construct;
    }

    public function inlineProductsList($cat, $scat, $sscat, $page){
        $products = Product::where('subsubcategory_id', $sscat)
            ->where('deleted', false)
            ->where('moderated', 0)
            ->where('published', true)
            ->where('current_stock', '>', 0)
            ->orderby('updated_at', 'asc')
            ->select('id', 'name')
            ->paginate(10, ['*'], 'page', $page);

        $products->appends($page)->links();

        //$products->setCurrentPage($page);
        //$thispage = $products->currentPage();
        $lastpage = $products->lastPage();
        //$prev = $thispage != '1' ? --$thispage : $thispage;
        //$next = $thispage != $lastpage ? ++$thispage : $thispage;
        $prev = $page == 1 ? 1 : $page - 1;
        if($page == $lastpage) $next = $page; else $next = $page + 1;
        $construct = array();

        foreach($products as $product){
            $construct[] = array(['text' => $product->name, 'callback_data' => 'cb_product_'.$cat.'_'.$scat.'_'.$sscat.'_'.$product->id]);
        }

        if($page == 1){
            $construct[] = array(['text' => '◀️', 'callback_data' => 'alert_paginationFirstpage'],['text' => $page.$this->text('pagination_of').$lastpage, 'callback_data' => 'alert_1'],['text' => '▶️', 'callback_data' => 'cb_sscat_'.$cat.'_'.$scat.'_'.$sscat.'_'.$next]);
        }elseif($page == $lastpage){
            $construct[] = array(['text' => '◀️', 'callback_data' => 'cb_sscat_'.$cat.'_'.$scat.'_'.$sscat.'_'.$prev],['text' => $page.$this->text('pagination_of').$lastpage, 'callback_data' => 'alert_1'],['text' => '▶️', 'callback_data' => 'alert_paginationLastpage']);
        }else{
            $construct[] = array(['text' => '◀️', 'callback_data' => 'cb_sscat_'.$cat.'_'.$scat.'_'.$sscat.'_'.$prev],['text' => $page.$this->text('pagination_of').$lastpage, 'callback_data' => 'alert_1'],['text' => '▶️', 'callback_data' => 'cb_sscat_'.$cat.'_'.$scat.'_'.$sscat.'_'.$next]);
        }

        $construct = array_merge($construct, [
            [
                ['text' => $this->text('button_cart'), 'callback_data' => 'cb_cart'],
                ['text' => $this->text('button_all_catalog'), 'callback_data' => 'cb_categories'],
                ['text' => $this->text('button_back_to_main'), 'callback_data' => 'cb_homepage']
            ],
            [['text' => $this->text('button_back'), 'callback_data' => 'cb_scat_'.$cat.'_'.$scat]],
        ]);
        return $construct;
    }

    public function inlineShowProduct($cat, $scat, $sscat, $prod, $qty = 1){
        $product = Product::where('id', $prod)->first();

        $construct = array();
        $construct[] = array(
                ['text' => '➖', 'callback_data' => 'productqty_qtyminus_'.$cat.'_'.$scat.'_'.$sscat.'_'.$prod.'_'.$qty],
                ['text' => $qty, 'callback_data' => 'viewqty'],
                ['text' => '➕', 'callback_data' => 'productqty_qtyplus_'.$cat.'_'.$scat.'_'.$sscat.'_'.$prod.'_'.$qty],
            );
        $construct[] = array(['text' => $this->text('button_buy_now'), 'callback_data' => 'put_tocartinoneclick_'.$prod.'_'.$qty]);
        /*$construct[] = array(
            ['text' => $this->text('button_cart'), 'callback_data' => 'cb_cart'],
            ['text' => $this->text('button_add_to_cart'), 'callback_data' => 'put_tocart_'.$prod.'_'.$qty],
        );*/
        //$construct[] = array(['text' => $this->text('button_back'), 'callback_data' => 'cb_sscat_back_'.$cat.'_'.$scat.'_'.$sscat.'_1']);
        $construct = array_merge($construct, [
            [
                ['text' => $this->text('button_share_with'), 'switch_inline_query' => $product->name],
                ['text' => $this->text('button_read_description'), 'callback_data' => 'producturl', 'url' => 'https://openshop.uz/product/'.$product->slug],
                //['text' => $this->text('button_all_catalog'), 'callback_data' => 'cb_categories'],
                //['text' => $this->text('button_back_to_main'), 'callback_data' => 'cb_homepage']
            ],
            [['text' => $this->text('button_back'), 'callback_data' => 'cb_sscat_back_'.$cat.'_'.$scat.'_'.$sscat.'_1']],
        ]);
        return $construct;
    }
    public function inlineShowProductByID($id, $qty = 1){
        $product = Product::where('id', $id)->first();
        $construct = array();

        $construct[] = array(
                ['text' => '➖', 'callback_data' => 'productbyid_qtyminus_'.$id.'_'.$qty],
                ['text' => $qty, 'callback_data' => 'viewqty'],
                ['text' => '➕', 'callback_data' => 'productbyid_qtyplus_'.$id.'_'.$qty],
            );
        $construct[] = array(

            ['text' => $this->text('button_share_with'), 'switch_inline_query' => $product->name],
            ['text' => $this->text('button_add_to_cart'), 'callback_data' => 'put_tocart_'.$id.'_'.$qty],

            );
        $construct = array_merge($construct, [
            [
                ['text' => $this->text('button_read_description'), 'callback_data' => 'producturl', 'url' => 'https://openshop.uz/product/'.$product->slug],
                ['text' => $this->text('button_cart'), 'callback_data' => 'cb_cart'],
            ],
            [['text' => $this->text('button_back'), 'callback_data' => 'cb_homepage']],
        ]);
        return $construct;
    }
    public function showProductImage($prod){
        $product = Product::where('id', $prod)->select('photos')->first();
        $images = json_decode($product->photos);
        $image = 'https://openshop.uz/public/'.$images[0];
        return $image;
    }
    public function showProductDetails($prod){
        $product = Product::where('id', $prod)->first();

        if(productIsDiscountable($product)){
            $price = "<b>".home_discounted_base_price($product->id, 'bot')."</b> (<strike>".home_base_price($product->id, 'bot')."</strike>)";
        }else{
            $price = "<b>".home_base_price($product->id, 'bot')."</b>";
        }

        $text = "<b>".$product->name."</b>\n\n".$this->text('string_price').$price;

        return $text;
    }

    public function getUserInfo($value, $chat_id, $translate = 1, $empty = 'empty'){
        $telegram_user = TelegramUser::where('chat_id', $chat_id)->first();
        $information = json_decode($telegram_user->information, 1);

        if(isset($information[$value]) && !empty($information[$value])){
            return $information[$value];
        }

        if($value == 'phone'){
            return $telegram_user->number;
        }

        if($translate){
            return $this->text($empty);
        }
        return false;
        //return $this->text($empty);
    }

    public function formatPrice($amount, $currency = 'uzs'){
        return number_format($amount)." ".$this->text($currency, $this->lang);
    }

    // $this->functions->translate

    public function text($key, $lang = 'ru'){
        //return isset($this->dictionary[$lang][$key]) ? $this->dictionary[$lang][$key] : $key;
        return isset($this->string->dictionary[$this->lang][$key]) ? $this->string->dictionary[$this->lang][$key] : $key;
    }
}
