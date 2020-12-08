<?php

$container = $app->getContainer();

$app->group('/v1', function () use ($app) {
	//sera chamado automaticamente pelo cron
	$app->get('/cron/firstInvoiceReminder', 'Controller\api\v1\Functions:reminderInvoicePayment');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/lastInvoiceReminder', 'Controller\api\v1\Functions:lastReminderInvoicePayment');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/reminderSchedulingDay', 'Controller\api\v1\Functions:reminderSchedulingDay');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/reminderSchedulingHour', 'Controller\api\v1\Functions:reminderSchedulingHour');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/tempEmailSms', 'Controller\api\v1\Functions:deleteTempMailSMS');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/mobile', 'Controller\api\v1\Functions:putOffCompanyMobile');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/chat', 'Controller\api\v1\Functions:deleteChat');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/deleteMobile', 'Controller\api\v1\Functions:deleteMobile');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/suspendCompany', 'Controller\api\v1\Functions:suspendCompany');
    //sera chamado automaticamente pelo cron
	$app->get('/cron/fechamento', 'Controller\api\v1\Functions:generateInvoice');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/nerd', 'Controller\api\v1\Functions:nerd');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/viewer', 'Controller\api\v1\Functions:viewer');
	//metodo chamdo pelo PAGHIPER
	$app->post('/boleto/update', 'Controller\api\v1\Functions:updateStatusBoleto');
    // Add a new user
	$app->post('/signup', 'Controller\api\v1\Functions:addUser');
	// Login System
	$app->post('/sendemail', 'Controller\api\v1\Functions:sendEmail');
	
	$app->post('/sendsms', 'Controller\api\v1\Functions:sendCodeFromSMS');

	$app->post('/verifycodeemail', 'Controller\api\v1\Functions:verifyCodeFromEmail');

	$app->post('/verifycodephonenumber', 'Controller\api\v1\Functions:verifyCodeFromPhoneNumber');

	$app->post('/tempNotification', 'Controller\api\v1\Functions:addTempNotification');

	//TERMOS
	$app->get('/legal/{type}', 'Controller\api\v1\Functions:getLegal');
	
	//SENDING IMAGE WITH S3
	$app->post('/upload/{folder},{id}', 'Controller\api\v1\Functions:uploadS3');

	$app->get('/companies/validation', 'Controller\api\v1\Functions:getCompaniesValidation');

	$app->post('/companies/validation', 'Controller\api\v1\Functions:addValidationMessage');

	$app->patch('/companies/validation', 'Controller\api\v1\Functions:updateValidationCompany');

	$app->delete('/companies/validation', 'Controller\api\v1\Functions:deleteValidationMessage');


	/*----------------------------------------------------USERS ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/
	$app->group('/users', function($app) {

		$app->get('/{userId}', 'Controller\api\v1\Functions:getUser'); 

		// DELETE a user with given id
		$app->delete('', 'Controller\api\v1\Functions:deleteUser'); 

		$app->delete('/logout', 'Controller\api\v1\Functions:logoutUser'); 

		// Update user with given id
		$app->patch('', 'Controller\api\v1\Functions:updateUser'); 

		$app->get('/coupon/{userId},{latitude},{longitude}', 'Controller\api\v1\Functions:getCouponUsers'); 

		$app->get('/coupon/cart/{userId},{companyId}', 'Controller\api\v1\Functions:getCouponUserFromCart'); 

		$app->post('/feedback', 'Controller\api\v1\Functions:addFeedback');

		$app->post('/suggestion', 'Controller\api\v1\Functions:addSuggestion');

		$app->get('/refreshtoken/newaccesstoken', 'Controller\api\v1\Functions:refreshAccessToken');

		$app->patch('/changephonenumber/verifycode', 'Controller\api\v1\Functions:verifyCodeToChangePhoneNumber');

		$app->patch('/changeemail/verifycode', 'Controller\api\v1\Functions:verifyCodeToChangeEmail');

		$app->post('/registerToken', 'Controller\api\v1\Functions:addUserNotification');

	});


	/*----------------------------------------------------STORE ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/

	$app->group('/company', function($app) {

		$app->get('/description/{companyId}', 'Controller\api\v1\Functions:getCompanyDescription');

		$app->patch('/visibility', 'Controller\api\v1\Functions:updateCompanyVisibility'); 

		$app->get('/validation/{companyId}', 'Controller\api\v1\Functions:getListAnalises'); 
		
		// get all company FOR USER and verify if user has itens on his cart
		$app->get('/{userId},{category}', 'Controller\api\v1\Functions:getCompanies'); 

			// get all company FOR USER by categoria and verify if user has itens on his cart
		$app->get('/category/{userId},{subcategory},{category}', 'Controller\api\v1\Functions:getCompaniesByCategory');

		$app->get('/user/{userId}[/{latitude}/{longitude}/{countryCode}]', 'Controller\api\v1\Functions:getCompanyByUserId');

		$app->get('/employee/{userId}[/{latitude}/{longitude}/{countryCode}]', 'Controller\api\v1\Functions:getCompanyByEmployeeId');

		$app->get('/user/main/{userId}', 'Controller\api\v1\Functions:getCompaniesByUserId');

		// Add a new company
		$app->post('/new/{planId}', 'Controller\api\v1\Functions:addCompany');

		$app->get('/coupon/{companyId}', 'Controller\api\v1\Functions:getCoupon');

		$app->post('/coupon/near/{category},{radius},{latitude},{longitude},{modelType}', 'Controller\api\v1\Functions:addCoupon');

				    // DELETE a company with given id
		$app->delete('', 'Controller\api\v1\Functions:deleteCompany');

				 //Update company with given id
		$app->patch('', 'Controller\api\v1\Functions:updateCompany'); 

			    //Update rating company with given id
		$app->patch('/rating', 'Controller\api\v1\Functions:OrderRating'); 

			      //Update rating company with given id
		$app->patch('/rating/scheduling', 'Controller\api\v1\Functions:SchedulingRating'); 

		$app->get('/data/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v1\Functions:getCompanyReport');

		$app->get('/data/scheduling/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v1\Functions:getCompanyReportScheduling');

		$app->get('/cashflow/{companyId},{data1},{data2}', 'Controller\api\v1\Functions:getFinance');

		$app->post('/payment', 'Controller\api\v1\Functions:addCompanyPayment');	

		$app->delete('/payment', 'Controller\api\v1\Functions:deleteCompanyPayment');

		$app->get('/payment/{companyId}', 'Controller\api\v1\Functions:getPaymentMethod');

		$app->get('/payment/checked/{companyId}', 'Controller\api\v1\Functions:getCompanyPayment');

		$app->post('/schedule', 'Controller\api\v1\Functions:addCompanySchedule');

		$app->patch('/schedule', 'Controller\api\v1\Functions:updateCompanySchedule');
		
		$app->delete('/schedule', 'Controller\api\v1\Functions:deleteCompanySchedule');

		$app->get('/schedule/{companyId}', 'Controller\api\v1\Functions:getCompanySchedule');

		$app->get('/schedule/undefined/{companyId}', 'Controller\api\v1\Functions:getUndefinedCompanySchedule');	

		$app->get('/register/subcategory/{category}', 'Controller\api\v1\Functions:getSubcategory');

		$app->post('/mobile', 'Controller\api\v1\Functions:mobileCompanyOn');

		$app->patch('/mobile', 'Controller\api\v1\Functions:mobileCompanyOff');	

		$app->patch('/mobile/update', 'Controller\api\v1\Functions:mobileCompanyUpdate');	

		$app->get('/invoice/{companyId}', 'Controller\api\v1\Functions:getInvoice');

		$app->get('/invoice/detail/{invoice_id}', 'Controller\api\v1\Functions:getInvoiceDetail');

		$app->get('/invoice/detail/order/{companyId},{date}', 'Controller\api\v1\Functions:getInvoiceOrderDetail');	

		$app->get('/invoice/detail/scheduling/{companyId},{date}', 'Controller\api\v1\Functions:getInvoiceSchedulingDetail');	

		$app->get('/plan/{categoryId}', 'Controller\api\v1\Functions:getPlan');

		$app->patch('/cashflow', 'Controller\api\v1\Functions:updateFinance');

		$app->delete('/cashflow', 'Controller\api\v1\Functions:deleteFinance');

		$app->post('/cashflow', 'Controller\api\v1\Functions:addFinance');

		$app->post('/checkcpfcnpj', 'Controller\api\v1\Functions:checkCompanyCPFCNPJ');

		$app->get('/scheduling/{companyId},{position},{latitude},{longitude},{countryCode}', 'Controller\api\v1\Functions:getSchedulingCompany');

		$app->post('/boleto/update', 'Controller\api\v1\Functions:updateCanceledBoleto');

		$app->get('/chat/{companyId}', 'Controller\api\v1\Functions:getChat');
	});


	/*----------------------------------------------------POSTS ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/


	$app->group('/nukno', function($app) {

		$app->get('/posts/{limit},{offset}', 'Controller\api\v1\Functions:getPosts');

		$app->get('/material/{userId}', 'Controller\api\v1\Functions:getMaterial');

		$app->get('/material/subcategory/{subcategory},{category}', 'Controller\api\v1\Functions:getMaterialByCategory');

		$app->get('/material/body/{materialId}', 'Controller\api\v1\Functions:getMaterialBody');
	});


	/*----------------------------------------------------Employee ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/

	$app->group('/employee', function($app) {
				//adicionar um usuario como Employee de um comercio
		$app->post('/company', 'Controller\api\v1\Functions:addEmployee');

			    //adicionar um Employee a um servico do comercio
		$app->post('/service', 'Controller\api\v1\Functions:addEmployeeService'); 

		    	//atualizar cadastro do Employee
		$app->patch('', 'Controller\api\v1\Functions:updateEmployee');

			    //deletar Employee
		$app->delete('/service', 'Controller\api\v1\Functions:deleteEmployeeService');

			        //deletar Employee
		$app->delete('', 'Controller\api\v1\Functions:deleteEmployee');

			    //pegar todos Employees do comercio
		$app->get('/company/{companyId}', 'Controller\api\v1\Functions:getListEmployee');

			     //pegar todos Employees de um servico do comercio
		$app->get('/service/{serviceId},{companyId}', 'Controller\api\v1\Functions:getListEmployeeService');

			        //pegar todos Employees de um servico do comercio
		$app->get('/service/unselected/{id},{companyId}', 'Controller\api\v1\Functions:getListEmployeeNotService');
	});


	/*----------------------------------------------------PRODUCTS ROUTES-----------------------------------------------------
	--------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/product', function($app) {
				// Retrieve products from id STORE
		$app->get('/company/{companyId}', 'Controller\api\v1\Functions:getListProduct'); 

			    // Retrieve products from id STORE
		$app->get('/item/{productId}', 'Controller\api\v1\Functions:getProduct'); 

				// Retrieve list of products and verify if user has itens in his cart
		$app->get('/{companyId},{userId}', 'Controller\api\v1\Functions:getListProductAndVerifyCart');

		//manual company
		$app->get('/companypos/{companyId},{userId}', 'Controller\api\v1\Functions:getListProductCompanyManual');

				    // Add a new product
		$app->post('', 'Controller\api\v1\Functions:addProduct');

				    // DELETE a product with given id
		$app->delete('', 'Controller\api\v1\Functions:deleteProduct'); 

				// Update products with given id
		$app->patch('/position', 'Controller\api\v1\Functions:updateProductPosition');


		$app->patch('/{id}', 'Controller\api\v1\Functions:updateProduct');

		//pega o item do id especico e j� retorna junto se existe esse item no carrinho do usuario
		$app->get('/detail/{productId}', 'Controller\api\v1\Functions:getProductDetail');
	});


	$app->group('/collection', function($app) {

		$app->get('/company/{companyId}', 'Controller\api\v1\Functions:getListCollection');

		$app->get('/detail/{id}', 'Controller\api\v1\Functions:getCollection');

		$app->post('', 'Controller\api\v1\Functions:addCollection');

		$app->delete('', 'Controller\api\v1\Functions:deleteCollection');

		$app->patch('', 'Controller\api\v1\Functions:updateCollection');

		$app->post('/extra', 'Controller\api\v1\Functions:addCollectionExtra');	

		$app->delete('/extra', 'Controller\api\v1\Functions:deleteCollectionExtra');

		$app->post('/product', 'Controller\api\v1\Functions:addProductCollection');
		
		$app->delete('/product', 'Controller\api\v1\Functions:deleteCollectionProduct');

		$app->get('/product/not/{companyId},{productId}', 'Controller\api\v1\Functions:getListCollectionNotProduct');

		$app->patch('/product/position', 'Controller\api\v1\Functions:updateProductCollectionPosition');

	});


	$app->group('/extra', function($app) {

		$app->get('/company/{companyId}', 'Controller\api\v1\Functions:getListExtra');

		$app->get('/collection/not/{companyId},{collectionId}', 'Controller\api\v1\Functions:getListExtraNotCollection');

		$app->post('', 'Controller\api\v1\Functions:addExtra');

		$app->delete('', 'Controller\api\v1\Functions:deleteExtra');

		$app->patch('', 'Controller\api\v1\Functions:updateExtra');

	});

	$app->group('/size', function($app) {

		$app->get('/company/{id}', 'Controller\api\v1\Functions:getListSize');

		$app->get('/product/not/{companyId},{productId}', 'Controller\api\v1\Functions:getListSizeNotProduct');

		$app->post('/product', 'Controller\api\v1\Functions:addSize');

		$app->patch('/product', 'Controller\api\v1\Functions:updateSize');
		
		$app->delete('/product', 'Controller\api\v1\Functions:deleteSize');

		$app->post('', 'Controller\api\v1\Functions:addSize');

		$app->delete('', 'Controller\api\v1\Functions:deleteSize');

		$app->patch('', 'Controller\api\v1\Functions:updateSize');

	});


		/*----------------------------------------------------SERVICOS ROUTES-----------------------------------------------------
		--------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/service', function($app) {
		// Retrieve services from id STORE
		$app->get('/company/{companyId}', 'Controller\api\v1\Functions:getListService'); 
		
		$app->get('/user/{companyId}', 'Controller\api\v1\Functions:getListServiceUser'); 

		    // adiciona servico
		$app->post('', 'Controller\api\v1\Functions:addService');

		$app->get('/scheduling/{serviceId},{data},{latitude},{longitude},{countyCode}', 'Controller\api\v1\Functions:getEmployeeScheduling');
		
		    // deleta sevico
		$app->delete('', 'Controller\api\v1\Functions:deleteService');
		
		// atualiza um servico
		$app->patch('', 'Controller\api\v1\Functions:updateService');

		$app->patch('/position', 'Controller\api\v1\Functions:updateServicePosition');

	});



		/*----------------------------------------------------SERVICOS ROUTES-----------------------------------------------------
		--------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/scheduling', function($app) {

		$app->patch('/company/status/{idStatus}[/{rating}]', 'Controller\api\v1\Functions:updateStatusScheduling');

		$app->patch('/user/status/{idStatus}', 'Controller\api\v1\Functions:updateStatusSchedulingFromUser');

		$app->get('/detail/{userId},{schedulingId},{latitude},{longitude},{countyCode}', 'Controller\api\v1\Functions:getScheduling');

		$app->post('', 'Controller\api\v1\Functions:addScheduling');

		$app->get('/history/{companyId},{data1},{data2}', 'Controller\api\v1\Functions:getSchedulingHistory');

		$app->get('/rating/{companyId},{data1},{data2}', 'Controller\api\v1\Functions:getSchedulingRating');
	});



	/*----------------------------------------------------ORDER ROUTES-----------------------------------------------------
	------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/orders', function($app) {
		// Retrieve company order with id 
		$app->get('/company/{companyId},{position}', 'Controller\api\v1\Functions:getCompanyOrderByStatus');

		$app->get('/history/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v1\Functions:getOrderHistory');

		$app->get('/rating/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v1\Functions:getOrderRating');

		// Retrieve user order with id, and ordid - detalhe pedido
		$app->get('/user/detail/{userId},{orderId},{latitude},{longitude},{countryCode}', 'Controller\api\v1\Functions:getOrderDetailUser');

		// Retrieve user orders with id 
		$app->get('/user/{userId},{position},{latitude},{longitude},{countryCode}', 'Controller\api\v1\Functions:getOrderUser'); 

		//Atualiza o status de uma order
		$app->patch('/company/status/{idStatus}[/{rating}]', 'Controller\api\v1\Functions:updateOrderStatus');
		$app->patch('/user/status/{idStatus}', 'Controller\api\v1\Functions:updateOrderStatusFromUser');

		// Add a new order              --------------------------------- REVISAR -----------------------------------------
		$app->post('', 'Controller\api\v1\Functions:addOrder'); 

		$app->post('/company', 'Controller\api\v1\Functions:addOrderCompany'); 

		$app->post('/chat/{tag}', 'Controller\api\v1\Functions:addMessageChat');

		$app->get('/chat/{orderId},{id}', 'Controller\api\v1\Functions:getMessageFromChat');
	});


	/*----------------------------------------------------CART ROUTES-----------------------------------------------------
	-----------------------------------------------------------------------------------------------------------------------*/
	$app->group('/cart', function($app) {
		// get user cart
		$app->get('/{userId},{latitude},{longitude},{countryCode}', 'Controller\api\v1\Functions:getCartUser');

		$app->get('/company/{userId},{companyId},{latitude},{longitude},{countryCode}', 'Controller\api\v1\Functions:getCartCompany');

		// Add a new product in cart's user
		$app->post('', 'Controller\api\v1\Functions:addCart');

			// Add a new product in cart's user
		$app->post('/company', 'Controller\api\v1\Functions:addCartCompany');

		//pega o item do id especico e j� retorna junto se existe esse item no carrinho do usuario
		$app->get('/item/{item},{userId},{cartId}', 'Controller\api\v1\Functions:getProductAndVerifyCart');

		//Atualiza quantidade do item no carrinho
		$app->patch('/item', 'Controller\api\v1\Functions:updateQtdItemCart');

		//Deleta um item do carrinho
		$app->delete('/item', 'Controller\api\v1\Functions:deleteItemCart'); 

		//Limpa o carrinho
		$app->delete('/clear', 'Controller\api\v1\Functions:deleteAllItemsUserCart'); 

		$app->patch('/info', 'Controller\api\v1\Functions:updateCartInfo');

		$app->patch('/info/coupon', 'Controller\api\v1\Functions:updateCartCouponInfo');
	});

	/*----------------------------------------------------ENDERE�O ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/
	$app->group('/address', function($app) {
		// pega todos os endere�os do usuario
		$app->get('/{userId}', 'Controller\api\v1\Functions:getAdressUser'); 

		//adiciona um novo endereco
		$app->post('', 'Controller\api\v1\Functions:addUserAddress');

		//Atualiza endere�o para selecionada como atual
		$app->patch('/addressIsSelected', 'Controller\api\v1\Functions:updateAddressToSelected');

		// Update adress
		$app->patch('', 'Controller\api\v1\Functions:updateAddress');

		//Delete endereco
		$app->delete('', 'Controller\api\v1\Functions:deleteAddress');
	});
});

$app->group('/v2', function () use ($app) {
	//sera chamado automaticamente pelo cron
	$app->get('/cron/firstInvoiceReminder', 'Controller\api\v2\Functions:reminderInvoicePayment');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/lastInvoiceReminder', 'Controller\api\v2\Functions:lastReminderInvoicePayment');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/reminderSchedulingDay', 'Controller\api\v2\Functions:reminderSchedulingDay');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/reminderSchedulingHour', 'Controller\api\v2\Functions:reminderSchedulingHour');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/tempEmailSms', 'Controller\api\v2\Functions:deleteTempMailSMS');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/mobile', 'Controller\api\v2\Functions:putOffCompanyMobile');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/chat', 'Controller\api\v2\Functions:deleteChat');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/deleteMobile', 'Controller\api\v2\Functions:deleteMobile');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/suspendCompany', 'Controller\api\v2\Functions:suspendCompany');
    //sera chamado automaticamente pelo cron
	$app->get('/cron/fechamento', 'Controller\api\v2\Functions:generateInvoice');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/nerd', 'Controller\api\v2\Functions:nerd');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/viewer', 'Controller\api\v2\Functions:viewer');
	//metodo chamdo pelo PAGHIPER
	$app->post('/boleto/update', 'Controller\api\v2\Functions:updateStatusBoleto');
    // Add a new user
	$app->post('/signup', 'Controller\api\v2\Functions:addUser');
	// Login System
	$app->post('/sendemail', 'Controller\api\v2\Functions:sendEmail');
	
	$app->post('/sendsms', 'Controller\api\v2\Functions:sendCodeFromSMS');

	$app->post('/verifycodeemail', 'Controller\api\v2\Functions:verifyCodeFromEmail');

	$app->post('/verifycodephonenumber', 'Controller\api\v2\Functions:verifyCodeFromPhoneNumber');

	$app->post('/tempNotification', 'Controller\api\v2\Functions:addTempNotification');

	$app->post('/company/tempNotification', 'Controller\api\v2\Functions:addTempNotificationCompany');

	//TERMOS
	$app->get('/legal/{type}', 'Controller\api\v2\Functions:getLegal');
	
	//SENDING IMAGE WITH S3
	$app->post('/upload/{folder},{id}', 'Controller\api\v2\Functions:uploadS3');

	$app->get('/companies/validation', 'Controller\api\v2\Functions:getCompaniesValidation');

	$app->post('/companies/validation', 'Controller\api\v2\Functions:addValidationMessage');

	$app->patch('/companies/validation', 'Controller\api\v2\Functions:updateValidationCompany');

	$app->delete('/companies/validation', 'Controller\api\v2\Functions:deleteValidationMessage');


	/*----------------------------------------------------USERS ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/
	$app->group('/users', function($app) {

		$app->get('/{userId}', 'Controller\api\v2\Functions:getUser'); 

		// DELETE a user with given id
		$app->delete('', 'Controller\api\v2\Functions:deleteUser'); 

		$app->delete('/logout', 'Controller\api\v2\Functions:logoutUser'); 

		// Update user with given id
		$app->patch('', 'Controller\api\v2\Functions:updateUser'); 

		$app->get('/coupon/{userId},{latitude},{longitude}', 'Controller\api\v2\Functions:getCouponUsers'); 

		$app->get('/coupon/cart/{userId},{companyId}', 'Controller\api\v2\Functions:getCouponUserFromCart'); 

		$app->post('/feedback', 'Controller\api\v2\Functions:addFeedback');

		$app->post('/suggestion', 'Controller\api\v2\Functions:addSuggestion');

		$app->post('/refreshtoken/newaccesstoken', 'Controller\api\v2\Functions:refreshAccessToken');

		$app->patch('/changephonenumber/verifycode', 'Controller\api\v2\Functions:verifyCodeToChangePhoneNumber');

		$app->patch('/changeemail/verifycode', 'Controller\api\v2\Functions:verifyCodeToChangeEmail');

		$app->post('/registerTokenNotification', 'Controller\api\v2\Functions:addUserNotification');

		$app->post('/company/registerTokenNotification', 'Controller\api\v2\Functions:addUserNotificationCompany');

	});


	/*----------------------------------------------------STORE ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/

	$app->group('/company', function($app) {

		$app->get('/description/{companyId}', 'Controller\api\v2\Functions:getCompanyDescription');

		$app->patch('/visibility', 'Controller\api\v2\Functions:updateCompanyVisibility'); 

		$app->get('/validation/{companyId}', 'Controller\api\v2\Functions:getListAnalises'); 
		
		// get all company FOR USER and verify if user has itens on his cart
		$app->get('/{userId},{category}', 'Controller\api\v2\Functions:getCompanies'); 

			// get all company FOR USER by categoria and verify if user has itens on his cart
		$app->get('/category/{userId},{subcategory},{category}', 'Controller\api\v2\Functions:getCompaniesByCategory');

		$app->get('/user/{userId}[/{latitude}/{longitude}/{countryCode}]', 'Controller\api\v2\Functions:getCompanyByUserId');

		$app->get('/employee/{userId}[/{latitude}/{longitude}/{countryCode}]', 'Controller\api\v2\Functions:getCompanyByEmployeeId');

		$app->get('/user/main/{userId}', 'Controller\api\v2\Functions:getCompaniesByUserId');

		// Add a new company
		$app->post('/new/{planId}', 'Controller\api\v2\Functions:addCompany');

		$app->get('/coupon/{companyId}', 'Controller\api\v2\Functions:getCoupon');

		$app->post('/coupon/near/{category},{radius},{latitude},{longitude},{modelType}', 'Controller\api\v2\Functions:addCoupon');

				    // DELETE a company with given id
		$app->delete('', 'Controller\api\v2\Functions:deleteCompany');

				 //Update company with given id
		$app->patch('', 'Controller\api\v2\Functions:updateCompany'); 

			    //Update rating company with given id
		$app->patch('/rating', 'Controller\api\v2\Functions:OrderRating'); 

			      //Update rating company with given id
		$app->patch('/rating/scheduling', 'Controller\api\v2\Functions:SchedulingRating'); 

		$app->get('/data/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v2\Functions:getCompanyReport');

		$app->get('/data/scheduling/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v2\Functions:getCompanyReportScheduling');

		$app->get('/cashflow/{companyId},{data1},{data2}', 'Controller\api\v2\Functions:getFinance');

		$app->post('/payment', 'Controller\api\v2\Functions:addCompanyPayment');	

		$app->delete('/payment', 'Controller\api\v2\Functions:deleteCompanyPayment');

		$app->get('/payment/{companyId}', 'Controller\api\v2\Functions:getPaymentMethod');

		$app->get('/payment/checked/{companyId}', 'Controller\api\v2\Functions:getCompanyPayment');

		$app->post('/schedule', 'Controller\api\v2\Functions:addCompanySchedule');

		$app->patch('/schedule', 'Controller\api\v2\Functions:updateCompanySchedule');
		
		$app->delete('/schedule', 'Controller\api\v2\Functions:deleteCompanySchedule');

		$app->get('/schedule/{companyId}', 'Controller\api\v2\Functions:getCompanySchedule');

		$app->get('/schedule/undefined/{companyId}', 'Controller\api\v2\Functions:getUndefinedCompanySchedule');	

		$app->get('/register/subcategory/{category}', 'Controller\api\v2\Functions:getSubcategory');

		$app->post('/mobile', 'Controller\api\v2\Functions:mobileCompanyOn');

		$app->patch('/mobile', 'Controller\api\v2\Functions:mobileCompanyOff');	

		$app->patch('/mobile/update', 'Controller\api\v2\Functions:mobileCompanyUpdate');	

		$app->get('/invoice/{companyId}', 'Controller\api\v2\Functions:getInvoice');

		$app->get('/invoice/detail/{invoice_id}', 'Controller\api\v2\Functions:getInvoiceDetail');

		$app->get('/invoice/detail/order/{companyId},{date}', 'Controller\api\v2\Functions:getInvoiceOrderDetail');	

		$app->get('/invoice/detail/scheduling/{companyId},{date}', 'Controller\api\v2\Functions:getInvoiceSchedulingDetail');	

		$app->get('/plan/{categoryId}', 'Controller\api\v2\Functions:getPlan');

		$app->patch('/cashflow', 'Controller\api\v2\Functions:updateFinance');

		$app->delete('/cashflow', 'Controller\api\v2\Functions:deleteFinance');

		$app->post('/cashflow', 'Controller\api\v2\Functions:addFinance');

		$app->post('/checkcpfcnpj', 'Controller\api\v2\Functions:checkCompanyCPFCNPJ');

		$app->get('/scheduling/{companyId},{position},{latitude},{longitude},{countryCode}', 'Controller\api\v2\Functions:getSchedulingCompany');

		$app->post('/boleto/update', 'Controller\api\v2\Functions:updateCanceledBoleto');

		$app->get('/chat/{companyId}', 'Controller\api\v2\Functions:getChat');
	});


	/*----------------------------------------------------POSTS ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/


	$app->group('/nukno', function($app) {

		$app->get('/posts/{limit},{offset}', 'Controller\api\v2\Functions:getPosts');

		$app->get('/material/{userId}', 'Controller\api\v2\Functions:getMaterial');

		$app->get('/material/subcategory/{subcategory},{category}', 'Controller\api\v2\Functions:getMaterialByCategory');

		$app->get('/material/body/{materialId}', 'Controller\api\v2\Functions:getMaterialBody');
	});


	/*----------------------------------------------------Employee ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/

	$app->group('/employee', function($app) {
				//adicionar um usuario como Employee de um comercio
		$app->post('/company', 'Controller\api\v2\Functions:addEmployee');

			    //adicionar um Employee a um servico do comercio
		$app->post('/service', 'Controller\api\v2\Functions:addEmployeeService'); 

		    	//atualizar cadastro do Employee
		$app->patch('', 'Controller\api\v2\Functions:updateEmployee');

			    //deletar Employee
		$app->delete('/service', 'Controller\api\v2\Functions:deleteEmployeeService');

			        //deletar Employee
		$app->delete('', 'Controller\api\v2\Functions:deleteEmployee');

			    //pegar todos Employees do comercio
		$app->get('/company/{companyId}', 'Controller\api\v2\Functions:getListEmployee');

			     //pegar todos Employees de um servico do comercio
		$app->get('/service/{serviceId},{companyId}', 'Controller\api\v2\Functions:getListEmployeeService');

			        //pegar todos Employees de um servico do comercio
		$app->get('/service/unselected/{id},{companyId}', 'Controller\api\v2\Functions:getListEmployeeNotService');
	});


	/*----------------------------------------------------PRODUCTS ROUTES-----------------------------------------------------
	--------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/product', function($app) {
		$app->get('/detail/{productId}', 'Controller\api\v2\Functions:getProductDetail');

				// Retrieve products from id STORE
		$app->get('/company/{companyId}', 'Controller\api\v2\Functions:getListProduct'); 

			    // Retrieve products from id STORE
		$app->get('/item/{productId}', 'Controller\api\v2\Functions:getProduct'); 

				// Retrieve list of products and verify if user has itens in his cart
		$app->get('/{companyId},{userId}', 'Controller\api\v2\Functions:getListProductAndVerifyCart');

		//manual company
		$app->get('/companypos/{companyId},{userId}', 'Controller\api\v2\Functions:getListProductCompanyManual');

				    // Add a new product
		$app->post('', 'Controller\api\v2\Functions:addProduct');

				    // DELETE a product with given id
		$app->delete('', 'Controller\api\v2\Functions:deleteProduct'); 

				// Update products with given id
		$app->patch('/position', 'Controller\api\v2\Functions:updateProductPosition');


		$app->patch('/{id}', 'Controller\api\v2\Functions:updateProduct');

		//pega o item do id especico e j� retorna junto se existe esse item no carrinho do usuario
	});


	$app->group('/collection', function($app) {

		$app->get('/company/{companyId}', 'Controller\api\v2\Functions:getListCollection');

		$app->get('/detail/{id}', 'Controller\api\v2\Functions:getCollection');

		$app->post('', 'Controller\api\v2\Functions:addCollection');

		$app->delete('', 'Controller\api\v2\Functions:deleteCollection');

		$app->patch('', 'Controller\api\v2\Functions:updateCollection');

		$app->post('/extra', 'Controller\api\v2\Functions:addCollectionExtra');	

		$app->delete('/extra', 'Controller\api\v2\Functions:deleteCollectionExtra');

		$app->post('/product', 'Controller\api\v2\Functions:addProductCollection');
		
		$app->delete('/product', 'Controller\api\v2\Functions:deleteCollectionProduct');

		$app->get('/product/not/{companyId},{productId}', 'Controller\api\v2\Functions:getListCollectionNotProduct');

		$app->patch('/product/position', 'Controller\api\v2\Functions:updateProductCollectionPosition');

	});


	$app->group('/extra', function($app) {

		$app->get('/company/{companyId}', 'Controller\api\v2\Functions:getListExtra');

		$app->get('/collection/not/{companyId},{collectionId}', 'Controller\api\v2\Functions:getListExtraNotCollection');

		$app->post('', 'Controller\api\v2\Functions:addExtra');

		$app->delete('', 'Controller\api\v2\Functions:deleteExtra');

		$app->patch('', 'Controller\api\v2\Functions:updateExtra');

	});

	$app->group('/size', function($app) {

		$app->get('/company/{id}', 'Controller\api\v2\Functions:getListSize');

		$app->get('/product/not/{companyId},{productId}', 'Controller\api\v2\Functions:getListSizeNotProduct');

		$app->post('/product', 'Controller\api\v2\Functions:addSize');

		$app->patch('/product', 'Controller\api\v2\Functions:updateSize');
		
		$app->delete('/product', 'Controller\api\v2\Functions:deleteSize');

		$app->post('', 'Controller\api\v2\Functions:addSize');

		$app->delete('', 'Controller\api\v2\Functions:deleteSize');

		$app->patch('', 'Controller\api\v2\Functions:updateSize');

	});


		/*----------------------------------------------------SERVICOS ROUTES-----------------------------------------------------
		--------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/service', function($app) {
		// Retrieve services from id STORE
		$app->get('/company/{companyId}', 'Controller\api\v2\Functions:getListService'); 
		
		$app->get('/user/{companyId}', 'Controller\api\v2\Functions:getListServiceUser'); 

		    // adiciona servico
		$app->post('', 'Controller\api\v2\Functions:addService');

		$app->get('/scheduling/{serviceId},{data},{latitude},{longitude},{countyCode}', 'Controller\api\v2\Functions:getEmployeeScheduling');
		
		    // deleta sevico
		$app->delete('', 'Controller\api\v2\Functions:deleteService');
		
		// atualiza um servico
		$app->patch('', 'Controller\api\v2\Functions:updateService');

		$app->patch('/position', 'Controller\api\v2\Functions:updateServicePosition');

	});



		/*----------------------------------------------------SERVICOS ROUTES-----------------------------------------------------
		--------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/scheduling', function($app) {

		$app->patch('/company/status/{idStatus}[/{rating}]', 'Controller\api\v2\Functions:updateStatusScheduling');

		$app->patch('/user/status/{idStatus}', 'Controller\api\v2\Functions:updateStatusSchedulingFromUser');

		$app->get('/detail/{userId},{schedulingId},{latitude},{longitude},{countyCode}', 'Controller\api\v2\Functions:getScheduling');

		$app->post('', 'Controller\api\v2\Functions:addScheduling');

		$app->get('/history/{companyId},{data1},{data2}', 'Controller\api\v2\Functions:getSchedulingHistory');

		$app->get('/rating/{companyId},{data1},{data2}', 'Controller\api\v2\Functions:getSchedulingRating');
	});



	/*----------------------------------------------------ORDER ROUTES-----------------------------------------------------
	------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/orders', function($app) {
		// Retrieve company order with id 
		$app->get('/company/{companyId},{position}', 'Controller\api\v2\Functions:getCompanyOrderByStatus');

		$app->get('/history/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v2\Functions:getOrderHistory');

		$app->get('/rating/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v2\Functions:getOrderRating');

		// Retrieve user order with id, and ordid - detalhe pedido
		$app->get('/user/detail/{userId},{orderId},{latitude},{longitude},{countryCode}', 'Controller\api\v2\Functions:getOrderDetailUser');

		// Retrieve user orders with id 
		$app->get('/user/{userId},{position},{latitude},{longitude},{countryCode}', 'Controller\api\v2\Functions:getOrderUser'); 

		//Atualiza o status de uma order
		$app->patch('/company/status/{idStatus}[/{rating}]', 'Controller\api\v2\Functions:updateOrderStatus');

		$app->patch('/user/status', 'Controller\api\v2\Functions:updateOrderStatusFromUser');

		// Add a new order              --------------------------------- REVISAR -----------------------------------------
		$app->post('', 'Controller\api\v2\Functions:addOrder'); 

		$app->post('/company', 'Controller\api\v2\Functions:addOrderCompany'); 

		$app->post('/chat/{tag}', 'Controller\api\v2\Functions:addMessageChat');

		$app->get('/chat/{orderId},{id}', 'Controller\api\v2\Functions:getMessageFromChat');
	});


	/*----------------------------------------------------CART ROUTES-----------------------------------------------------
	-----------------------------------------------------------------------------------------------------------------------*/
	$app->group('/cart', function($app) {
		// get user cart
		$app->get('/{userId},{latitude},{longitude},{countryCode}', 'Controller\api\v2\Functions:getCartUser');

		$app->get('/company/{userId},{companyId},{latitude},{longitude},{countryCode}', 'Controller\api\v2\Functions:getCartCompany');

		// Add a new product in cart's user
		$app->post('', 'Controller\api\v2\Functions:addCart');

			// Add a new product in cart's user
		$app->post('/company', 'Controller\api\v2\Functions:addCartCompany');

		//pega o item do id especico e j� retorna junto se existe esse item no carrinho do usuario
		$app->get('/item/{item},{userId},{cartId}', 'Controller\api\v2\Functions:getProductAndVerifyCart');

		//Atualiza quantidade do item no carrinho
		$app->patch('/item', 'Controller\api\v2\Functions:updateQtdItemCart');

		//Deleta um item do carrinho
		$app->delete('/item', 'Controller\api\v2\Functions:deleteItemCart'); 

		//Limpa o carrinho
		$app->delete('/clear', 'Controller\api\v2\Functions:deleteAllItemsUserCart'); 

		$app->patch('/info', 'Controller\api\v2\Functions:updateCartInfo');

		$app->patch('/info/coupon', 'Controller\api\v2\Functions:updateCartCouponInfo');
	});

	/*----------------------------------------------------ENDERE�O ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/
	$app->group('/address', function($app) {
		// pega todos os endere�os do usuario
		$app->get('/{userId}', 'Controller\api\v2\Functions:getAdressUser'); 

		//adiciona um novo endereco
		$app->post('', 'Controller\api\v2\Functions:addUserAddress');

		//Atualiza endere�o para selecionada como atual
		$app->patch('/addressIsSelected', 'Controller\api\v2\Functions:updateAddressToSelected');

		// Update adress
		$app->patch('', 'Controller\api\v2\Functions:updateAddress');

		//Delete endereco
		$app->delete('', 'Controller\api\v2\Functions:deleteAddress');
	});
});

$app->group('/v3', function () use ($app) {
	//sera chamado automaticamente pelo cron
	$app->get('/cron/firstInvoiceReminder', 'Controller\api\v3\Functions:reminderInvoicePayment');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/lastInvoiceReminder', 'Controller\api\v3\Functions:lastReminderInvoicePayment');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/reminderSchedulingDay', 'Controller\api\v3\Functions:reminderSchedulingDay');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/reminderSchedulingHour', 'Controller\api\v3\Functions:reminderSchedulingHour');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/tempEmailSms', 'Controller\api\v3\Functions:deleteTempMailSMS');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/mobile', 'Controller\api\v3\Functions:putOffCompanyMobile');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/chat', 'Controller\api\v3\Functions:deleteChat');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/deleteMobile', 'Controller\api\v3\Functions:deleteMobile');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/suspendCompany', 'Controller\api\v3\Functions:suspendCompany');
    //sera chamado automaticamente pelo cron
	$app->get('/cron/fechamento', 'Controller\api\v3\Functions:generateInvoice');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/nerd', 'Controller\api\v3\Functions:nerd');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/viewer', 'Controller\api\v3\Functions:viewer');
	//metodo chamdo pelo PAGHIPER
	$app->post('/boleto/update', 'Controller\api\v3\Functions:updateStatusBoleto');
    // Add a new user
	$app->post('/signup', 'Controller\api\v3\Functions:addUser');
	// Login System
	$app->post('/sendemail', 'Controller\api\v3\Functions:sendEmail');
	
	$app->post('/sendsms', 'Controller\api\v3\Functions:sendCodeFromSMS');

	$app->post('/verifycodeemail', 'Controller\api\v3\Functions:verifyCodeFromEmail');

	$app->post('/verifycodephonenumber', 'Controller\api\v3\Functions:verifyCodeFromPhoneNumber');

	$app->post('/tempNotification', 'Controller\api\v3\Functions:addTempNotification');

	$app->post('/company/tempNotification', 'Controller\api\v3\Functions:addTempNotificationCompany');

	//TERMOS
	$app->get('/legal/{type}', 'Controller\api\v3\Functions:getLegal');
	
	//SENDING IMAGE WITH S3
	$app->post('/upload/{folder},{id}', 'Controller\api\v3\Functions:uploadS3');

	$app->get('/companies/validation', 'Controller\api\v3\Functions:getCompaniesValidation');

	$app->post('/companies/validation', 'Controller\api\v3\Functions:addValidationMessage');

	$app->patch('/companies/validation', 'Controller\api\v3\Functions:updateValidationCompany');

	$app->delete('/companies/validation', 'Controller\api\v3\Functions:deleteValidationMessage');


	/*----------------------------------------------------USERS ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/
	$app->group('/users', function($app) {

		$app->get('/{userId}', 'Controller\api\v3\Functions:getUser'); 

		// DELETE a user with given id
		$app->delete('', 'Controller\api\v3\Functions:deleteUser'); 

		$app->delete('/logout', 'Controller\api\v3\Functions:logoutUser'); 

		// Update user with given id
		$app->patch('', 'Controller\api\v3\Functions:updateUser'); 

		$app->get('/coupon/{userId},{latitude},{longitude}', 'Controller\api\v3\Functions:getCouponUsers'); 

		$app->get('/coupon/cart/{userId},{companyId}', 'Controller\api\v3\Functions:getCouponUserFromCart'); 

		$app->post('/feedback', 'Controller\api\v3\Functions:addFeedback');

		$app->post('/suggestion', 'Controller\api\v3\Functions:addSuggestion');

		$app->post('/refreshtoken/newaccesstoken', 'Controller\api\v3\Functions:refreshAccessToken');

		$app->patch('/changephonenumber/verifycode', 'Controller\api\v3\Functions:verifyCodeToChangePhoneNumber');

		$app->patch('/changeemail/verifycode', 'Controller\api\v3\Functions:verifyCodeToChangeEmail');

		$app->post('/registerTokenNotification', 'Controller\api\v3\Functions:addUserNotification');

		$app->post('/company/registerTokenNotification', 'Controller\api\v3\Functions:addUserNotificationCompany');

	});


	/*----------------------------------------------------STORE ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/

	$app->group('/company', function($app) {

		$app->get('/description/{companyId}', 'Controller\api\v3\Functions:getCompanyDescription');

		$app->patch('/visibility', 'Controller\api\v3\Functions:updateCompanyVisibility'); 

		$app->get('/validation/{companyId}', 'Controller\api\v3\Functions:getListAnalises'); 
		
		// get all company FOR USER and verify if user has itens on his cart
		$app->get('/{userId},{category}', 'Controller\api\v3\Functions:getCompanies'); 

			// get all company FOR USER by categoria and verify if user has itens on his cart
		$app->get('/category/{userId},{subcategory},{category}', 'Controller\api\v3\Functions:getCompaniesByCategory');

		$app->get('/user/{userId}[/{latitude}/{longitude}/{countryCode}]', 'Controller\api\v3\Functions:getCompanyByUserId');

		$app->get('/employee/{userId}[/{latitude}/{longitude}/{countryCode}]', 'Controller\api\v3\Functions:getCompanyByEmployeeId');

		$app->get('/user/main/{userId}', 'Controller\api\v3\Functions:getCompaniesByUserId');

		// Add a new company
		$app->post('/new/{planId}', 'Controller\api\v3\Functions:addCompany');

		$app->get('/coupon/{companyId}', 'Controller\api\v3\Functions:getCoupon');

		$app->post('/coupon/near/{category},{radius},{latitude},{longitude},{modelType}', 'Controller\api\v3\Functions:addCoupon');

				    // DELETE a company with given id
		$app->delete('', 'Controller\api\v3\Functions:deleteCompany');

				 //Update company with given id
		$app->patch('', 'Controller\api\v3\Functions:updateCompany'); 

			    //Update rating company with given id
		$app->patch('/rating', 'Controller\api\v3\Functions:OrderRating'); 

			      //Update rating company with given id
		$app->patch('/rating/scheduling', 'Controller\api\v3\Functions:SchedulingRating'); 

		$app->get('/data/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v3\Functions:getCompanyReport');

		$app->get('/data/scheduling/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v3\Functions:getCompanyReportScheduling');

		$app->get('/cashflow/{companyId},{data1},{data2}', 'Controller\api\v3\Functions:getFinance');

		$app->post('/payment', 'Controller\api\v3\Functions:addCompanyPayment');	

		$app->delete('/payment', 'Controller\api\v3\Functions:deleteCompanyPayment');

		$app->get('/payment/{companyId}', 'Controller\api\v3\Functions:getPaymentMethod');

		$app->get('/payment/checked/{companyId}', 'Controller\api\v3\Functions:getCompanyPayment');

		$app->post('/schedule', 'Controller\api\v3\Functions:addCompanySchedule');

		$app->patch('/schedule', 'Controller\api\v3\Functions:updateCompanySchedule');
		
		$app->delete('/schedule', 'Controller\api\v3\Functions:deleteCompanySchedule');

		$app->get('/schedule/{companyId}', 'Controller\api\v3\Functions:getCompanySchedule');

		$app->get('/schedule/undefined/{companyId}', 'Controller\api\v3\Functions:getUndefinedCompanySchedule');	

		$app->get('/register/subcategory/{category}', 'Controller\api\v3\Functions:getSubcategory');

		$app->post('/mobile', 'Controller\api\v3\Functions:mobileCompanyOn');

		$app->patch('/mobile', 'Controller\api\v3\Functions:mobileCompanyOff');	

		$app->patch('/mobile/update', 'Controller\api\v3\Functions:mobileCompanyUpdate');	

		$app->get('/invoice/{companyId}', 'Controller\api\v3\Functions:getInvoice');

		$app->get('/invoice/detail/{invoice_id}', 'Controller\api\v3\Functions:getInvoiceDetail');

		$app->get('/invoice/detail/order/{companyId},{date}', 'Controller\api\v3\Functions:getInvoiceOrderDetail');	

		$app->get('/invoice/detail/scheduling/{companyId},{date}', 'Controller\api\v3\Functions:getInvoiceSchedulingDetail');	

		$app->get('/plan/{categoryId}', 'Controller\api\v3\Functions:getPlan');

		$app->patch('/cashflow', 'Controller\api\v3\Functions:updateFinance');

		$app->delete('/cashflow', 'Controller\api\v3\Functions:deleteFinance');

		$app->post('/cashflow', 'Controller\api\v3\Functions:addFinance');

		$app->post('/checkcpfcnpj', 'Controller\api\v3\Functions:checkCompanyCPFCNPJ');

		$app->get('/scheduling/{companyId},{position},{latitude},{longitude},{countryCode}', 'Controller\api\v3\Functions:getSchedulingCompany');

		$app->post('/boleto/update', 'Controller\api\v3\Functions:updateCanceledBoleto');

		$app->get('/chat/{companyId}', 'Controller\api\v3\Functions:getChat');
	});


	/*----------------------------------------------------POSTS ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/


	$app->group('/nukno', function($app) {

		$app->get('/posts/{limit},{offset}', 'Controller\api\v3\Functions:getPosts');

		$app->get('/material/{userId}', 'Controller\api\v3\Functions:getMaterial');

		$app->get('/material/subcategory/{subcategory},{category}', 'Controller\api\v3\Functions:getMaterialByCategory');

		$app->get('/material/body/{materialId}', 'Controller\api\v3\Functions:getMaterialBody');
	});


	/*----------------------------------------------------Employee ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/

	$app->group('/employee', function($app) {
				//adicionar um usuario como Employee de um comercio
		$app->post('/company', 'Controller\api\v3\Functions:addEmployee');

			    //adicionar um Employee a um servico do comercio
		$app->post('/service', 'Controller\api\v3\Functions:addEmployeeService'); 

		    	//atualizar cadastro do Employee
		$app->patch('', 'Controller\api\v3\Functions:updateEmployee');

			    //deletar Employee
		$app->delete('/service', 'Controller\api\v3\Functions:deleteEmployeeService');

			        //deletar Employee
		$app->delete('', 'Controller\api\v3\Functions:deleteEmployee');

			    //pegar todos Employees do comercio
		$app->get('/company/{companyId}', 'Controller\api\v3\Functions:getListEmployee');

			     //pegar todos Employees de um servico do comercio
		$app->get('/service/{serviceId},{companyId}', 'Controller\api\v3\Functions:getListEmployeeService');

			        //pegar todos Employees de um servico do comercio
		$app->get('/service/unselected/{id},{companyId}', 'Controller\api\v3\Functions:getListEmployeeNotService');
	});


	/*----------------------------------------------------PRODUCTS ROUTES-----------------------------------------------------
	--------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/product', function($app) {
				// Retrieve products from id STORE
		$app->get('/company/{companyId}', 'Controller\api\v3\Functions:getListProduct'); 

			    // Retrieve products from id STORE
		$app->get('/item/{productId}', 'Controller\api\v3\Functions:getProduct'); 

				// Retrieve list of products and verify if user has itens in his cart
		$app->get('/{companyId},{userId}', 'Controller\api\v3\Functions:getListProductAndVerifyCart');

		//manual company
		$app->get('/companypos/{companyId},{userId}', 'Controller\api\v3\Functions:getListProductCompanyManual');

				    // Add a new product
		$app->post('', 'Controller\api\v3\Functions:addProduct');

				    // DELETE a product with given id
		$app->delete('', 'Controller\api\v3\Functions:deleteProduct'); 

				// Update products with given id
		$app->patch('/position', 'Controller\api\v3\Functions:updateProductPosition');


		$app->patch('/{id}', 'Controller\api\v3\Functions:updateProduct');

		//pega o item do id especico e j� retorna junto se existe esse item no carrinho do usuario
		$app->get('/detail/{productId}', 'Controller\api\v3\Functions:getProductDetail');
	});


	$app->group('/collection', function($app) {

		$app->get('/company/{companyId}', 'Controller\api\v3\Functions:getListCollection');

		$app->get('/detail/{id}', 'Controller\api\v3\Functions:getCollection');

		$app->post('', 'Controller\api\v3\Functions:addCollection');

		$app->delete('', 'Controller\api\v3\Functions:deleteCollection');

		$app->patch('', 'Controller\api\v3\Functions:updateCollection');

		$app->post('/extra', 'Controller\api\v3\Functions:addCollectionExtra');	

		$app->delete('/extra', 'Controller\api\v3\Functions:deleteCollectionExtra');

		$app->post('/product', 'Controller\api\v3\Functions:addProductCollection');
		
		$app->delete('/product', 'Controller\api\v3\Functions:deleteCollectionProduct');

		$app->get('/product/not/{companyId},{productId}', 'Controller\api\v3\Functions:getListCollectionNotProduct');

		$app->patch('/product/position', 'Controller\api\v3\Functions:updateProductCollectionPosition');

	});


	$app->group('/extra', function($app) {

		$app->get('/company/{companyId}', 'Controller\api\v3\Functions:getListExtra');

		$app->get('/collection/not/{companyId},{collectionId}', 'Controller\api\v3\Functions:getListExtraNotCollection');

		$app->post('', 'Controller\api\v3\Functions:addExtra');

		$app->delete('', 'Controller\api\v3\Functions:deleteExtra');

		$app->patch('', 'Controller\api\v3\Functions:updateExtra');

	});

	$app->group('/size', function($app) {

		$app->get('/company/{id}', 'Controller\api\v3\Functions:getListSize');

		$app->get('/product/not/{companyId},{productId}', 'Controller\api\v3\Functions:getListSizeNotProduct');

		$app->post('/product', 'Controller\api\v3\Functions:addSize');

		$app->patch('/product', 'Controller\api\v3\Functions:updateSize');
		
		$app->delete('/product', 'Controller\api\v3\Functions:deleteSize');

		$app->post('', 'Controller\api\v3\Functions:addSize');

		$app->delete('', 'Controller\api\v3\Functions:deleteSize');

		$app->patch('', 'Controller\api\v3\Functions:updateSize');

	});


		/*----------------------------------------------------SERVICOS ROUTES-----------------------------------------------------
		--------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/service', function($app) {
		// Retrieve services from id STORE
		$app->get('/company/{companyId}', 'Controller\api\v3\Functions:getListService'); 
		
		$app->get('/user/{companyId}', 'Controller\api\v3\Functions:getListServiceUser'); 

		    // adiciona servico
		$app->post('', 'Controller\api\v3\Functions:addService');

		$app->get('/scheduling/{serviceId},{data},{latitude},{longitude},{countyCode}', 'Controller\api\v3\Functions:getEmployeeScheduling');
		
		    // deleta sevico
		$app->delete('', 'Controller\api\v3\Functions:deleteService');
		
		// atualiza um servico
		$app->patch('', 'Controller\api\v3\Functions:updateService');

		$app->patch('/position', 'Controller\api\v3\Functions:updateServicePosition');

	});



		/*----------------------------------------------------SERVICOS ROUTES-----------------------------------------------------
		--------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/scheduling', function($app) {

		$app->patch('/company/status/{idStatus}[/{rating}]', 'Controller\api\v3\Functions:updateStatusScheduling');

		$app->patch('/user/status/{idStatus}', 'Controller\api\v3\Functions:updateStatusSchedulingFromUser');

		$app->get('/detail/{userId},{schedulingId},{latitude},{longitude},{countyCode}', 'Controller\api\v3\Functions:getScheduling');

		$app->post('', 'Controller\api\v3\Functions:addScheduling');

		$app->get('/history/{companyId},{data1},{data2}', 'Controller\api\v3\Functions:getSchedulingHistory');

		$app->get('/rating/{companyId},{data1},{data2}', 'Controller\api\v3\Functions:getSchedulingRating');
	});



	/*----------------------------------------------------ORDER ROUTES-----------------------------------------------------
	------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/orders', function($app) {
		// Retrieve company order with id 
		$app->get('/company/{companyId},{position}', 'Controller\api\v3\Functions:getCompanyOrderByStatus');

		$app->get('/history/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v3\Functions:getOrderHistory');

		$app->get('/rating/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v3\Functions:getOrderRating');

		// Retrieve user order with id, and ordid - detalhe pedido
		$app->get('/user/detail/{userId},{orderId},{latitude},{longitude},{countryCode}', 'Controller\api\v3\Functions:getOrderDetailUser');

		// Retrieve user orders with id 
		$app->get('/user/{userId},{position},{latitude},{longitude},{countryCode}', 'Controller\api\v3\Functions:getOrderUser'); 

		//Atualiza o status de uma order
		$app->patch('/company/status/{idStatus}[/{rating}]', 'Controller\api\v3\Functions:updateOrderStatus');
		$app->patch('/user/status/{idStatus}', 'Controller\api\v3\Functions:updateOrderStatusFromUser');

		// Add a new order              --------------------------------- REVISAR -----------------------------------------
		$app->post('', 'Controller\api\v3\Functions:addOrder'); 

		$app->post('/company', 'Controller\api\v3\Functions:addOrderCompany'); 

		$app->post('/chat/{tag}', 'Controller\api\v3\Functions:addMessageChat');

		$app->get('/chat/{orderId},{id}', 'Controller\api\v3\Functions:getMessageFromChat');
	});


	/*----------------------------------------------------CART ROUTES-----------------------------------------------------
	-----------------------------------------------------------------------------------------------------------------------*/
	$app->group('/cart', function($app) {
		// get user cart
		$app->get('/{userId},{latitude},{longitude},{countryCode}', 'Controller\api\v3\Functions:getCartUser');

		$app->get('/company/{userId},{companyId},{latitude},{longitude},{countryCode}', 'Controller\api\v3\Functions:getCartCompany');

		// Add a new product in cart's user
		$app->post('', 'Controller\api\v3\Functions:addCart');

			// Add a new product in cart's user
		$app->post('/company', 'Controller\api\v3\Functions:addCartCompany');

		//pega o item do id especico e j� retorna junto se existe esse item no carrinho do usuario
		$app->get('/item/{item},{userId},{cartId}', 'Controller\api\v3\Functions:getProductAndVerifyCart');

		//Atualiza quantidade do item no carrinho
		$app->patch('/item', 'Controller\api\v3\Functions:updateQtdItemCart');

		//Deleta um item do carrinho
		$app->delete('/item', 'Controller\api\v3\Functions:deleteItemCart'); 

		//Limpa o carrinho
		$app->delete('/clear', 'Controller\api\v3\Functions:deleteAllItemsUserCart'); 

		$app->patch('/info', 'Controller\api\v3\Functions:updateCartInfo');

		$app->patch('/info/coupon', 'Controller\api\v3\Functions:updateCartCouponInfo');
	});

	/*----------------------------------------------------ENDERE�O ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/
	$app->group('/address', function($app) {
		// pega todos os endere�os do usuario
		$app->get('/{userId}', 'Controller\api\v3\Functions:getAdressUser'); 

		//adiciona um novo endereco
		$app->post('', 'Controller\api\v3\Functions:addUserAddress');

		//Atualiza endere�o para selecionada como atual
		$app->patch('/addressIsSelected', 'Controller\api\v3\Functions:updateAddressToSelected');

		// Update adress
		$app->patch('', 'Controller\api\v3\Functions:updateAddress');

		//Delete endereco
		$app->delete('', 'Controller\api\v3\Functions:deleteAddress');
	});
});

$app->group('/v4', function () use ($app) {
	//sera chamado automaticamente pelo cron
	$app->get('/cron/firstInvoiceReminder', 'Controller\api\v4\Functions:reminderInvoicePayment');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/lastInvoiceReminder', 'Controller\api\v4\Functions:lastReminderInvoicePayment');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/reminderSchedulingDay', 'Controller\api\v4\Functions:reminderSchedulingDay');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/reminderSchedulingHour', 'Controller\api\v4\Functions:reminderSchedulingHour');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/tempEmailSms', 'Controller\api\v4\Functions:deleteTempMailSMS');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/mobile', 'Controller\api\v4\Functions:putOffCompanyMobile');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/chat', 'Controller\api\v4\Functions:deleteChat');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/deleteMobile', 'Controller\api\v4\Functions:deleteMobile');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/suspendCompany', 'Controller\api\v4\Functions:suspendCompany');
    //sera chamado automaticamente pelo cron
	$app->get('/cron/fechamento', 'Controller\api\v4\Functions:generateInvoice');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/nerd', 'Controller\api\v4\Functions:nerd');
	//sera chamado automaticamente pelo cron
	$app->get('/cron/viewer', 'Controller\api\v4\Functions:viewer');
	//metodo chamdo pelo PAGHIPER
	$app->post('/boleto/update', 'Controller\api\v4\Functions:updateStatusBoleto');
    // Add a new user
	$app->post('/signup', 'Controller\api\v4\Functions:addUser');
	// Login System
	$app->post('/sendemail', 'Controller\api\v4\Functions:sendEmail');
	
	$app->post('/sendsms', 'Controller\api\v4\Functions:sendCodeFromSMS');

	$app->post('/verifycodeemail', 'Controller\api\v4\Functions:verifyCodeFromEmail');

	$app->post('/verifycodephonenumber', 'Controller\api\v4\Functions:verifyCodeFromPhoneNumber');

	$app->post('/tempNotification', 'Controller\api\v4\Functions:addTempNotification');

	$app->post('/company/tempNotification', 'Controller\api\v4\Functions:addTempNotificationCompany');

	//TERMOS
	$app->get('/legal/{type}', 'Controller\api\v4\Functions:getLegal');
	
	//SENDING IMAGE WITH S3
	$app->post('/upload/{folder},{id}', 'Controller\api\v4\Functions:uploadS3');

	$app->get('/companies/validation', 'Controller\api\v4\Functions:getCompaniesValidation');

	$app->post('/companies/validation', 'Controller\api\v4\Functions:addValidationMessage');

	$app->patch('/companies/validation', 'Controller\api\v4\Functions:updateValidationCompany');

	$app->delete('/companies/validation', 'Controller\api\v4\Functions:deleteValidationMessage');


	/*----------------------------------------------------USERS ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/
	$app->group('/users', function($app) {
		
		$app->get('/indication/{userId}', 'Controller\api\v4\Functions:getIndication'); 

		$app->get('/transaction/{userId}', 'Controller\api\v4\Functions:getTransaction'); 

		$app->get('/affiliated/{userId}', 'Controller\api\v4\Functions:getUserAffiliatedCompany'); 
		
		$app->post('/cashout', 'Controller\api\v4\Functions:cashOutUser'); 

		$app->get('/{userId}', 'Controller\api\v4\Functions:getUser'); 

		$app->get('/userAndAddress/{userId}', 'Controller\api\v4\Functions:getUserAndAddress'); 

		// DELETE a user with given id
		$app->delete('', 'Controller\api\v4\Functions:deleteUser'); 

		$app->delete('/logout', 'Controller\api\v4\Functions:logoutUser'); 

		// Update user with given id
		$app->patch('', 'Controller\api\v4\Functions:updateUser'); 

		$app->get('/coupon/{userId},{latitude},{longitude}', 'Controller\api\v4\Functions:getCouponUsers'); 

		$app->get('/coupon/cart/{userId},{companyId}', 'Controller\api\v4\Functions:getCouponUserFromCart'); 

		$app->post('/feedback', 'Controller\api\v4\Functions:addFeedback');

		$app->post('/suggestion', 'Controller\api\v4\Functions:addSuggestion');

		$app->post('/refreshtoken/newaccesstoken', 'Controller\api\v4\Functions:refreshAccessToken');

		$app->patch('/changephonenumber/verifycode', 'Controller\api\v4\Functions:verifyCodeToChangePhoneNumber');

		$app->patch('/changeemail/verifycode', 'Controller\api\v4\Functions:verifyCodeToChangeEmail');

		$app->post('/registerTokenNotification', 'Controller\api\v4\Functions:addUserNotification');

		$app->post('/company/registerTokenNotification', 'Controller\api\v4\Functions:addUserNotificationCompany');

	});


	/*----------------------------------------------------STORE ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/

	$app->group('/company', function($app) {

		$app->post('/indication', 'Controller\api\v4\Functions:addIndication');

		$app->get('/description/{companyId}', 'Controller\api\v4\Functions:getCompanyDescription');

		$app->get('/checkDomain/{domain}', 'Controller\api\v4\Functions:getCompanyDomain');

		$app->get('/checkApp/{companyId}/{userId}', 'Controller\api\v4\Functions:getCompanyApp');

		$app->patch('/visibility', 'Controller\api\v4\Functions:updateCompanyVisibility'); 

		$app->get('/validation/{companyId}', 'Controller\api\v4\Functions:getListAnalises'); 
		
		// get all company FOR USER and verify if user has itens on his cart
		$app->get('/{userId},{category}', 'Controller\api\v4\Functions:getCompanies'); 

			// get all company FOR USER by categoria and verify if user has itens on his cart
		$app->get('/category/{userId},{subcategory}', 'Controller\api\v4\Functions:getCompaniesByCategory');

		$app->get('/user/{userId}[/{latitude}/{longitude}/{countryCode}]', 'Controller\api\v4\Functions:getCompanyByUserId');

		$app->get('/employee/{userId}[/{latitude}/{longitude}/{countryCode}]', 'Controller\api\v4\Functions:getCompanyByEmployeeId');

		$app->get('/user/main/{userId}', 'Controller\api\v4\Functions:getCompaniesByUserId');

		// Add a new company
		$app->post('/new/{planId}', 'Controller\api\v4\Functions:addCompany');

		$app->get('/coupon/{companyId}', 'Controller\api\v4\Functions:getCoupon');

		$app->post('/coupon/near/{category},{radius},{latitude},{longitude},{modelType}', 'Controller\api\v4\Functions:addCoupon');

				    // DELETE a company with given id
		$app->delete('', 'Controller\api\v4\Functions:deleteCompany');

				 //Update company with given id
		$app->patch('', 'Controller\api\v4\Functions:updateCompany'); 

			    //Update rating company with given id
		$app->patch('/rating', 'Controller\api\v4\Functions:OrderRating'); 

			      //Update rating company with given id
		$app->patch('/rating/scheduling', 'Controller\api\v4\Functions:SchedulingRating'); 

		$app->get('/data/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v4\Functions:getCompanyReport');

		$app->get('/data/scheduling/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v4\Functions:getCompanyReportScheduling');

		$app->get('/cashflow/{companyId},{data1},{data2}', 'Controller\api\v4\Functions:getFinance');

		$app->post('/payment', 'Controller\api\v4\Functions:addCompanyPayment');	

		$app->delete('/payment', 'Controller\api\v4\Functions:deleteCompanyPayment');

		$app->get('/payment/{companyId}', 'Controller\api\v4\Functions:getPaymentMethod');

		$app->get('/payment/checked/{companyId}', 'Controller\api\v4\Functions:getCompanyPayment');

		$app->post('/schedule', 'Controller\api\v4\Functions:addCompanySchedule');

		$app->patch('/schedule', 'Controller\api\v4\Functions:updateCompanySchedule');
		
		$app->delete('/schedule', 'Controller\api\v4\Functions:deleteCompanySchedule');

		$app->get('/schedule/{companyId}', 'Controller\api\v4\Functions:getCompanySchedule');

		$app->get('/schedule/undefined/{companyId}', 'Controller\api\v4\Functions:getUndefinedCompanySchedule');	

		$app->get('/register/subcategory/{category}', 'Controller\api\v4\Functions:getSubcategory');

		$app->post('/mobile', 'Controller\api\v4\Functions:mobileCompanyOn');

		$app->patch('/mobile', 'Controller\api\v4\Functions:mobileCompanyOff');	

		$app->patch('/mobile/update', 'Controller\api\v4\Functions:mobileCompanyUpdate');	

		$app->get('/invoice/{companyId}', 'Controller\api\v4\Functions:getInvoice');

		$app->get('/invoice/detail/{invoice_id}', 'Controller\api\v4\Functions:getInvoiceDetail');

		$app->get('/invoice/detail/order/{companyId},{date}', 'Controller\api\v4\Functions:getInvoiceOrderDetail');	

		$app->get('/invoice/detail/scheduling/{companyId},{date}', 'Controller\api\v4\Functions:getInvoiceSchedulingDetail');	

		$app->get('/plan/{categoryId}', 'Controller\api\v4\Functions:getPlan');

		$app->patch('/cashflow', 'Controller\api\v4\Functions:updateFinance');

		$app->delete('/cashflow', 'Controller\api\v4\Functions:deleteFinance');

		$app->post('/cashflow', 'Controller\api\v4\Functions:addFinance');

		$app->post('/checkcpfcnpj', 'Controller\api\v4\Functions:checkCompanyCPFCNPJ');

		$app->get('/scheduling/{companyId},{position},{latitude},{longitude},{countryCode}', 'Controller\api\v4\Functions:getSchedulingCompany');

		$app->post('/boleto/update', 'Controller\api\v4\Functions:updateCanceledBoleto');

		$app->get('/chat/{companyId}', 'Controller\api\v4\Functions:getChat');
	});


	/*----------------------------------------------------POSTS ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/


	$app->group('/nukno', function($app) {

		$app->get('/posts/{limit},{offset}', 'Controller\api\v4\Functions:getPosts');

		$app->get('/material/{userId}', 'Controller\api\v4\Functions:getMaterial');

		$app->get('/material/subcategory/{subcategory},{category}', 'Controller\api\v4\Functions:getMaterialByCategory');

		$app->get('/material/body/{materialId}', 'Controller\api\v4\Functions:getMaterialBody');
	});


	/*----------------------------------------------------Employee ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/

	$app->group('/employee', function($app) {
				//adicionar um usuario como Employee de um comercio
		$app->post('/company', 'Controller\api\v4\Functions:addEmployee');

			    //adicionar um Employee a um servico do comercio
		$app->post('/service', 'Controller\api\v4\Functions:addEmployeeService'); 

		    	//atualizar cadastro do Employee
		$app->patch('', 'Controller\api\v4\Functions:updateEmployee');

			    //deletar Employee
		$app->delete('/service', 'Controller\api\v4\Functions:deleteEmployeeService');

			        //deletar Employee
		$app->delete('', 'Controller\api\v4\Functions:deleteEmployee');

			    //pegar todos Employees do comercio
		$app->get('/company/{companyId}', 'Controller\api\v4\Functions:getListEmployee');

			     //pegar todos Employees de um servico do comercio
		$app->get('/service/{serviceId},{companyId}', 'Controller\api\v4\Functions:getListEmployeeService');

			        //pegar todos Employees de um servico do comercio
		$app->get('/service/unselected/{id},{companyId}', 'Controller\api\v4\Functions:getListEmployeeNotService');
	});


	/*----------------------------------------------------PRODUCTS ROUTES-----------------------------------------------------
	--------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/product', function($app) {
				// Retrieve products from id STORE
		$app->get('/company/{companyId}', 'Controller\api\v4\Functions:getListProduct'); 

			    // Retrieve products from id STORE
		$app->get('/item/{productId}', 'Controller\api\v4\Functions:getProduct'); 

		//pega o item do id especico e j� retorna junto se existe esse item no carrinho do usuario
		$app->get('/detail/{productId}', 'Controller\api\v4\Functions:getProductDetail');

				// Retrieve list of products and verify if user has itens in his cart
		$app->get('/user/{companyId}[/{userId}/{source}]', 'Controller\api\v4\Functions:getListProductAndVerifyCart');

		//manual company
		$app->get('/companypos/{companyId},{userId}', 'Controller\api\v4\Functions:getListProductCompanyManual');

				    // Add a new product
		$app->post('', 'Controller\api\v4\Functions:addProduct');

				    // DELETE a product with given id
		$app->delete('', 'Controller\api\v4\Functions:deleteProduct'); 

				// Update products with given id
		$app->patch('/position', 'Controller\api\v4\Functions:updateProductPosition');


		$app->patch('/{id}', 'Controller\api\v4\Functions:updateProduct');
	});


	$app->group('/collection', function($app) {

		$app->get('/company/{companyId}', 'Controller\api\v4\Functions:getListCollection');

		$app->get('/detail/{id}', 'Controller\api\v4\Functions:getCollection');

		$app->post('', 'Controller\api\v4\Functions:addCollection');

		$app->delete('', 'Controller\api\v4\Functions:deleteCollection');

		$app->patch('', 'Controller\api\v4\Functions:updateCollection');

		$app->post('/extra', 'Controller\api\v4\Functions:addCollectionExtra');	

		$app->delete('/extra', 'Controller\api\v4\Functions:deleteCollectionExtra');

		$app->post('/product', 'Controller\api\v4\Functions:addProductCollection');
		
		$app->delete('/product', 'Controller\api\v4\Functions:deleteCollectionProduct');

		$app->get('/product/not/{companyId},{productId}', 'Controller\api\v4\Functions:getListCollectionNotProduct');

		$app->patch('/product/position', 'Controller\api\v4\Functions:updateProductCollectionPosition');

	});


	$app->group('/extra', function($app) {

		$app->get('/company/{companyId}', 'Controller\api\v4\Functions:getListExtra');

		$app->get('/collection/not/{companyId},{collectionId}', 'Controller\api\v4\Functions:getListExtraNotCollection');

		$app->post('', 'Controller\api\v4\Functions:addExtra');

		$app->delete('', 'Controller\api\v4\Functions:deleteExtra');

		$app->patch('', 'Controller\api\v4\Functions:updateExtra');

	});

	$app->group('/size', function($app) {

		$app->get('/company/{id}', 'Controller\api\v4\Functions:getListSize');

		$app->get('/product/not/{companyId},{productId}', 'Controller\api\v4\Functions:getListSizeNotProduct');

		$app->post('/product', 'Controller\api\v4\Functions:addSize');

		$app->patch('/product', 'Controller\api\v4\Functions:updateSize');
		
		$app->delete('/product', 'Controller\api\v4\Functions:deleteSize');

		$app->post('', 'Controller\api\v4\Functions:addSize');

		$app->delete('', 'Controller\api\v4\Functions:deleteSize');

		$app->patch('', 'Controller\api\v4\Functions:updateSize');

	});


		/*----------------------------------------------------SERVICOS ROUTES-----------------------------------------------------
		--------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/service', function($app) {
		// Retrieve services from id STORE
		$app->get('/company/{companyId}', 'Controller\api\v4\Functions:getListService'); 
		
		$app->get('/user/{companyId}', 'Controller\api\v4\Functions:getListServiceUser'); 

		    // adiciona servico
		$app->post('', 'Controller\api\v4\Functions:addService');

		$app->get('/scheduling/{serviceId},{data},{latitude},{longitude},{countyCode}', 'Controller\api\v4\Functions:getEmployeeScheduling');
		
		    // deleta sevico
		$app->delete('', 'Controller\api\v4\Functions:deleteService');
		
		// atualiza um servico
		$app->patch('', 'Controller\api\v4\Functions:updateService');

		$app->patch('/position', 'Controller\api\v4\Functions:updateServicePosition');

	});



		/*----------------------------------------------------SERVICOS ROUTES-----------------------------------------------------
		--------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/scheduling', function($app) {

		$app->patch('/company/status/{idStatus}[/{rating}]', 'Controller\api\v4\Functions:updateStatusScheduling');

		$app->patch('/user/status/{idStatus}', 'Controller\api\v4\Functions:updateStatusSchedulingFromUser');

		$app->get('/detail/{userId},{schedulingId},{latitude},{longitude},{countyCode}', 'Controller\api\v4\Functions:getScheduling');

		$app->post('', 'Controller\api\v4\Functions:addScheduling');

		$app->get('/history/{companyId},{data1},{data2}', 'Controller\api\v4\Functions:getSchedulingHistory');

		$app->get('/rating/{companyId},{data1},{data2}', 'Controller\api\v4\Functions:getSchedulingRating');
	});



	/*----------------------------------------------------ORDER ROUTES-----------------------------------------------------
	------------------------------------------------------------------------------------------------------------------------*/

	$app->group('/orders', function($app) {
		// Retrieve company order with id 
		$app->get('/company/{companyId},{position}', 'Controller\api\v4\Functions:getCompanyOrderByStatus');

		$app->get('/history/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v4\Functions:getOrderHistory');

		$app->get('/rating/{companyId},{data1},{data2},{latitude},{longitude},{countryCode}', 'Controller\api\v4\Functions:getOrderRating');

		// Retrieve user order with id, and ordid - detalhe pedido
		$app->get('/user/detail/{userId},{orderId},{latitude},{longitude},{countryCode}', 'Controller\api\v4\Functions:getOrderDetailUser');

		// Retrieve user orders with id 
		$app->get('/user/{userId},{source},{position},{latitude},{longitude},{countryCode}', 'Controller\api\v4\Functions:getOrderUser'); 

		//Atualiza o status de uma order
		$app->patch('/company/status/{idStatus}[/{rating}]', 'Controller\api\v4\Functions:updateOrderStatus');
		$app->patch('/user/status', 'Controller\api\v4\Functions:updateOrderStatusFromUser');

		// Add a new order              --------------------------------- REVISAR -----------------------------------------
		$app->post('', 'Controller\api\v4\Functions:addOrder'); 

		$app->post('/company', 'Controller\api\v4\Functions:addOrderCompany'); 

		$app->post('/chat/{tag}', 'Controller\api\v4\Functions:addMessageChat');

		$app->get('/chat/{orderId},{id}', 'Controller\api\v4\Functions:getMessageFromChat');
	});


	/*----------------------------------------------------CART ROUTES-----------------------------------------------------
	-----------------------------------------------------------------------------------------------------------------------*/
	$app->group('/cart', function($app) {
		// get user cart
		$app->get('/{userId}/{source}/{latitude}/{longitude}/{countryCode}[/{hasPayments}]', 'Controller\api\v4\Functions:getCartUser');

		$app->get('/company/{userId},{companyId},{latitude},{longitude},{countryCode}', 'Controller\api\v4\Functions:getCartCompany');

		// Add a new product in cart's user
		$app->post('', 'Controller\api\v4\Functions:addCart');

			// Add a new product in cart's user
		$app->post('/company', 'Controller\api\v4\Functions:addCartCompany');

		//pega o item do id especico e j� retorna junto se existe esse item no carrinho do usuario
		$app->get('/item/{item},{userId},{cartId}', 'Controller\api\v4\Functions:getProductAndVerifyCart');

		//Atualiza quantidade do item no carrinho
		$app->patch('/item', 'Controller\api\v4\Functions:updateQtdItemCart');

		//Deleta um item do carrinho
		$app->delete('/item', 'Controller\api\v4\Functions:deleteItemCart'); 

		//Limpa o carrinho
		$app->delete('/clear', 'Controller\api\v4\Functions:deleteAllItemsUserCart'); 

		$app->patch('/info', 'Controller\api\v4\Functions:updateCartInfo');

		$app->patch('/info/coupon', 'Controller\api\v4\Functions:updateCartCouponInfo');
	});

	/*----------------------------------------------------ENDERE�O ROUTES-----------------------------------------------------
	----------------------------------------------------------------------------------------------------------------------*/
	$app->group('/address', function($app) {
		// pega todos os endere�os do usuario
		$app->get('/{userId}', 'Controller\api\v4\Functions:getAddressUser');
		
		// pega somente o endereço atual
		$app->get('/selected/{userId}', 'Controller\api\v4\Functions:getUserSelectedAddress'); 

		//adiciona um novo endereco
		$app->post('', 'Controller\api\v4\Functions:addUserAddress');

		//Atualiza endere�o para selecionada como atual
		$app->patch('/addressIsSelected', 'Controller\api\v4\Functions:updateAddressToSelected');

		// Update adress
		$app->patch('', 'Controller\api\v4\Functions:updateAddress');

		//Delete endereco
		$app->delete('', 'Controller\api\v4\Functions:deleteAddress');
	});
});