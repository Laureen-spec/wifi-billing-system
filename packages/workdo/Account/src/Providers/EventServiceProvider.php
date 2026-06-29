<?php

namespace Workdo\Account\Providers;

use App\Events\ApprovePurchaseReturn;
use App\Events\ApproveSalesReturn;
use App\Events\CreateTransfer;
use App\Events\DefaultData;
use App\Events\DestroyTransfer;
use App\Events\GivePermissionToRole;
use App\Events\PostPurchaseInvoice;
use App\Events\PostSalesInvoice;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Workdo\Account\Listeners\ApproveHolidayzRoomBookingListener;
use Workdo\Account\Listeners\ApproveMedicalOrderPaymentListener;
use Workdo\Account\Listeners\ApprovePettyCashListener;
use Workdo\Account\Listeners\BankAccountFieldUpdate;
use Workdo\Account\Listeners\CreateDebitNoteFromReturn;
use Workdo\Account\Listeners\CreateCreditNoteFromReturn;
use Workdo\Account\Listeners\UpdateMobileServicePaymentStatusLis;
use Workdo\Account\Listeners\DataDefault;
use Workdo\Account\Listeners\PostPurchaseInvoiceListener;
use Workdo\Account\Listeners\CreateTransferListener;
use Workdo\Account\Listeners\DestroyTransferListener;
use Workdo\Account\Listeners\GiveRoleToPermission;
use Workdo\Account\Listeners\PostSalesInvoiceListener;
use Workdo\Account\Listeners\PostProjectPaymentListener;
use Workdo\Account\Listeners\UpdateRetainerPaymentStatusListener;
use Workdo\Retainer\Events\UpdateRetainerPaymentStatus;
use Workdo\Account\Listeners\UpdateCommissionPaymentStatusListener;
use Workdo\Commission\Events\UpdateCommissionPaymentStatus;
use Workdo\Account\Listeners\PaySalaryListener;
use Workdo\Hrm\Events\PaySalary;
use Workdo\Account\Listeners\CreatePosListener;
use Workdo\Account\Listeners\ApprovePosReturnListener;
use Workdo\Fleet\Events\MarkFleetBookingPaymentPaid;
use Workdo\MobileServiceManagement\Events\UpdateMobileServicePaymentStatus;
use Workdo\Pos\Events\CreatePos;
use Workdo\Pos\Events\ApprovePosReturn;
use Workdo\Account\Listeners\MarkFleetBookingPaymentPaidListener;
use Workdo\Fleet\Events\CraeteFleetBookingPayment;
use Workdo\MobileServiceManagement\Events\CreateMobileServicePayment;
use Workdo\Account\Listeners\BeautyBookingPaymentListener;
use Workdo\DairyCattleManagement\Events\CreateDairyCattlePayment;
use Workdo\DairyCattleManagement\Events\UpdateDairyCattlePaymentStatus;
use Workdo\Account\Listeners\UpdateDairyCattlePaymentStatusListener;
use Workdo\CateringManagement\Events\CreateCateringOrderPayment;
use Workdo\CateringManagement\Events\UpdateCateringOrderPaymentStatus;
use Workdo\Account\Listeners\UpdateCateringOrderPaymentStatusListener;
use Workdo\Account\Listeners\UpdateSalesAgentCommissionPaymentStatusLis;
use Workdo\Account\Listeners\ApproveSalesAgentCommissionAdjustmentLis;
use Workdo\Account\Listeners\CompleteWarrantyClaimListener;
use Workdo\Account\Listeners\ConvertSalesRetainerListener;
use Workdo\Account\Listeners\MarkBeautyBookingPaymentPaidListener;
use Workdo\Account\Listeners\UpdateDairyCattleExpenseTrackingStatusListener;
use Workdo\BeautySpaManagement\Events\BeautyBookingPayments;
use Workdo\BeautySpaManagement\Events\CreateBeautyBookingPayment;
use Workdo\BeautySpaManagement\Events\MarkBeautyBookingPaymentPaid;
use Workdo\CateringManagement\Events\CreateCateringExpenseTracking;
use Workdo\CateringManagement\Events\MarkCateringExpenseTrackingAsPaid;
use Workdo\CateringManagement\Events\UpdateCateringExpenseTracking;
use Workdo\Commission\Events\CreateCommissionPayment;
use Workdo\CourierManagement\Events\CreateCourierPayment;
use Workdo\CourierManagement\Events\UpdateCourierPayment;
use Workdo\DairyCattleManagement\Events\CreateDairyCattleExpenseTracking;
use Workdo\DairyCattleManagement\Events\UpdateDairyCattleExpenseTracking;
use Workdo\DairyCattleManagement\Events\UpdateDairyCattleExpenseTrackingStatus;
use Workdo\EventsManagement\Events\CreateEventBookingPayment;
use Workdo\Fleet\Events\CreateFleetExpense;
use Workdo\Fleet\Events\UpdateFleetExpense;
use Workdo\GymManagement\Events\CreateMembershipPlanPayment;
use Workdo\PropertyManagement\Events\CreatePropertyPayment;
use Workdo\Hrm\Events\CreatePayroll;
use Workdo\Hrm\Events\UpdatePayroll;
use Workdo\LaundryManagement\Events\CreateLaundryExpense;
use Workdo\LaundryManagement\Events\CreateLaundryPayment;
use Workdo\LaundryManagement\Events\UpdateLaundryExpense;
use Workdo\LegalCaseManagement\Events\CreateCaseExpense;
use Workdo\LegalCaseManagement\Events\CreateFeeReceive;
use Workdo\LegalCaseManagement\Events\UpdateCaseExpense;
use Workdo\LegalCaseManagement\Events\UpdateFeeReceive;
use Workdo\MedicalLabManagement\Events\CreateMedicalOrderPayment;
use Workdo\ParkingManagement\Events\CreateParkingPayment;
use Workdo\RentalManagement\Events\CreateRentalMaintenance;
use Workdo\RentalManagement\Events\UpdateRentalMaintenance;
use Workdo\Retainer\Events\ConvertSalesRetainer;
use Workdo\SalesAgent\Events\CreateSalesAgentCommissionPayment;
use Workdo\SalesAgent\Events\UpdateSalesAgentCommissionPaymentStatus;
use Workdo\SalesAgent\Events\ApproveSalesAgentCommissionAdjustment;
use Workdo\SocietyManagement\Events\CreateSocietyMaintenanceBillPayment;
use Workdo\VehicleBookingManagement\Events\CreateVehicleBookingPayment;
use Workdo\Warranty\Events\CreateWarrantyClaim;
use Workdo\Account\Listeners\MarkCateringExpenseTrackingAsPaidListener;
use Workdo\Holidayz\Events\CreateHolidayzRoomBooking;
use Workdo\Holidayz\Events\HolidayzBookingPayments;
use Workdo\Holidayz\Events\UpdateHolidayzRoomBooking;
use Workdo\Account\Listeners\HolidayzBookingPaymentsListener;
use Workdo\Fleet\Events\PostFleetExpense;
use Workdo\Holidayz\Events\ApproveHolidayzRoomBooking;
use Workdo\Account\Listeners\PostFleetExpenseListener;
use Workdo\EventsManagement\Events\UpdateEventBookingPaymentStatus;
use Workdo\Account\Listeners\UpdateEventBookingPaymentStatusListener;
use Workdo\EventsManagement\Events\EventBookingPayments;
use Workdo\Account\Listeners\EventBookingPaymentsListener;
use Workdo\Account\Listeners\LaundryBookingPaymentsListener;
use Workdo\Account\Listeners\MarkCaseExpenseAsPaidListner;
use Workdo\Account\Listeners\MarkFeeReceiveAsClearedListener;
use Workdo\Account\Listeners\UpdateVehicleBookingPaymentStatusListener;
use Workdo\Account\Listeners\VehicleBookingPaymentsListener;
use Workdo\GymManagement\Events\MembershipPlanAssigned;
use Workdo\LegalCaseManagement\Events\MarkCaseExpenseAsPaid;
use Workdo\LegalCaseManagement\Events\MarkFeeReceiveAsCleared;
use Workdo\VehicleBookingManagement\Events\UpdateVehicleBookingPaymentStatus;
use Workdo\VehicleBookingManagement\Events\VehicleBookingPayments;
use Workdo\Account\Listeners\MembershipPlanAssignedListener;
use Workdo\Account\Listeners\ParkingBookingPaymentsListener;
use Workdo\Account\Listeners\UpdateLaundryExpenseStatusListener;
use Workdo\Account\Listeners\UpdateLaundryPaymentStatusListener;
use Workdo\Account\Listeners\UpdateParkingPaymentStatusListener;
use Workdo\LaundryManagement\Events\LaundryBookingPayments;
use Workdo\LaundryManagement\Events\UpdateLaundryExpenseStatus;
use Workdo\LaundryManagement\Events\UpdateLaundryPaymentStatus;
use Workdo\MedicalLabManagement\Events\ApproveMedicalOrderPayment;
use Workdo\ParkingManagement\Events\ParkingBookingPayments;
use Workdo\ParkingManagement\Events\UpdateParkingPaymentStatus;
use Workdo\PettyCashManagement\Events\ApprovePettyCash;
use Workdo\PettyCashManagement\Events\CreatePettyCash;
use Workdo\PettyCashManagement\Events\UpdatePettyCash;
use Workdo\Warranty\Events\CompleteWarrantyClaim;
use Workdo\Pos\Events\CreatePosBillingCounter;
use Workdo\Pos\Events\UpdatePosBillingCounter;
use Workdo\VehicleTrade\Events\CreateVehicletradePayment;
use Workdo\VehicleTrade\Events\CreateServiceHistory;
use Workdo\VehicleTrade\Events\UpdateServiceHistory;
use Workdo\OpticalAndEyeCareCenter\Events\CreateEyewearOrder;
use Workdo\OpticalAndEyeCareCenter\Events\UpdateEyewearOrder; 
use Workdo\MovieShowBookingSystem\Events\CreateMovieFoodOrder;
use Workdo\MovieShowBookingSystem\Events\UpdateMovieFoodOrder;
use Workdo\SecurityGuardManagement\Events\CreateSecurityPayment;
use Workdo\TailoringFashiondesign\Events\CreateTailorPayment;
use Workdo\LockerAndSafeDeposit\Events\CreateLockerPayment;
use Workdo\Consultancy\Events\CreateConsultancyPayment;
use Workdo\MovieShowBookingSystem\Events\CreateMovieBookingPayment;
use Workdo\DietAndNutritionConsultant\Events\CreateDietMemberPayment;
use Workdo\DietAndNutritionConsultant\Events\CreateDietAppointment;
use Workdo\DJAndOrchestraManagement\Events\CreateDJOrchestraEventPayment;
use Workdo\DJAndOrchestraManagement\Events\CreateDJAndOrchestraContract;
use Workdo\GrantManagement\Events\CreateGrantApplicationPayment;    
use Workdo\OfficeEquipmentManagement\Events\CreateOfficeMaintenanceLog;
use Workdo\OfficeEquipmentManagement\Events\UpdateOfficeMaintenanceLog;
use Workdo\ElderlyCare\Events\CreateElderlyCareRequest;
use Workdo\ElderlyCare\Events\UpdateElderlyCareRequest;
use Workdo\GameZone\Events\CreateGameRental;
use Workdo\GameZone\Events\UpdateGameRental;
use Workdo\GameZone\Events\CreateGameMembershipPayment;
use Workdo\GameZone\Events\CreateGameFoodOrder;
use Workdo\GameZone\Events\UpdateGameFoodOrder;
use Workdo\LibraryManagement\Events\CreateLibraryBookFinePayment;
use Workdo\NGOManagment\Events\CreateNgoDonation;
use Workdo\NGOManagment\Events\UpdateNgoDonation;
use Workdo\NGOManagment\Events\CreateNgoFundUtilization;
use Workdo\NGOManagment\Events\UpdateNgoFundUtilization;
use Workdo\FranchiseManagement\Events\CreateFranchiseAgreement;
use Workdo\FranchiseManagement\Events\UpdateFranchiseAgreement;
use Workdo\FranchiseManagement\Events\CreateFranchiseProfit;
use Workdo\FranchiseManagement\Events\UpdateFranchiseProfit;
use Workdo\PrintPressManagement\Events\CreateMachineMaintenanceRecord;
use Workdo\PrintPressManagement\Events\UpdateMachineMaintenanceRecord;
use Workdo\PrintPressManagement\Events\CreatePressOrder;
use Workdo\PrintPressManagement\Events\UpdatePressOrder;
use Workdo\PrintPressManagement\Events\CreatePressExpense;
use Workdo\PrintPressManagement\Events\UpdatePressExpense;
use Workdo\BakeryStore\Events\CreateBakeryExpense;
use Workdo\BakeryStore\Events\UpdateBakeryExpense;
use Workdo\BakeryStore\Events\CreateBakeryStoreOrder;
use Workdo\BakeryStore\Events\UpdateBakeryStoreOrder;
use Workdo\CoworkingSpaceManagement\Events\CreateCoworkingMembership;
use Workdo\CoworkingSpaceManagement\Events\UpdateCoworkingMembership;
use Workdo\CoworkingSpaceManagement\Events\CreateCoworkingBooking;
use Workdo\CoworkingSpaceManagement\Events\UpdateCoworkingBooking;
use Workdo\MusicInstitute\Events\CreateMusicPayment;
use Workdo\MusicInstitute\Events\CreateMusicInstrumentMaintenance;
use Workdo\MusicInstitute\Events\UpdateMusicInstrumentMaintenance;
use Workdo\SportsClubAndAcademyManagement\Events\AssignSportsClubMembership;
use Workdo\SportsClubAndAcademyManagement\Events\CreateSportsClubAndGroundOrder;
use Workdo\SportsClubAndAcademyManagement\Events\UpdateSportsClubAndGroundOrder;
use Workdo\EquipmentRental\Events\CreateEquipmentRentalRepair;
use Workdo\EquipmentRental\Events\UpdateEquipmentRentalRepair;
use Workdo\ArtShowcase\Events\CreateArtWorkOrderPayment;
use Workdo\ArtShowcase\Events\UpdateArtWorkOrderPayment;
use Workdo\EquipmentRental\Events\CreateEquipmentRentalBookingPayment;
use Workdo\InfluencerMarketing\Events\CreateInfluencerMarketingPayoutPayment;
use Workdo\DanceAcademy\Events\CreateDanceFee;
use Workdo\WaterParkManagement\Events\CreateWaterParkPayment;
use Workdo\WaterParkManagement\Events\CreateWaterParkClothingSales;
use Workdo\WaterParkManagement\Events\UpdateWaterParkClothingSales;
use Workdo\WaterParkManagement\Events\CreateWaterParkMaintenance;
use Workdo\WaterParkManagement\Events\UpdateWaterParkMaintenance;
use Workdo\TattooStudioManagement\Events\CreateTattooPayment;
use Workdo\BloodBank\Events\CreateBloodRequestPayment;
use Workdo\SolarHub\Events\CreateSolarHubMaintenance;
use Workdo\SolarHub\Events\UpdateSolarHubMaintenance;
use Workdo\SolarHub\Events\CreateSolarHubPayment;
use Workdo\TVStudio\Events\CreateTvStudioOrder;
use Workdo\TVStudio\Events\UpdateTvStudioOrder;
use Workdo\Newspaper\Events\CreateNewspaperAdvertisement;
use Workdo\Newspaper\Events\UpdateNewspaperAdvertisement;
use Workdo\Newspaper\Events\CreateNewspaperSubscription;
use Workdo\Newspaper\Events\UpdateNewspaperSubscription;
use Workdo\Newspaper\Events\CreateNewspaperPayment;
use Workdo\VehicleWash\Events\CreateVehicleWashBookingPayment;
use Workdo\CctvSecuritySystem\Events\CreateCctvOrder;
use Workdo\CctvSecuritySystem\Events\UpdateCctvOrder;
use Workdo\HairAndCareStudio\Events\CreateHairCarePayment;
use Workdo\PetCare\Events\CreatePetCareBookingPayment;
use Workdo\PetCare\Events\CreatePetCareMembershipPayment;
use Workdo\PetCare\Events\PostPetCareAdoptionRequest;
use Workdo\BoutiqueAndDesignerStudio\Events\CreateBoutiquePayment;
use Workdo\BoutiqueAndDesignerStudio\Events\CreateBoutiqueDamage;
use Workdo\BoutiqueAndDesignerStudio\Events\CreateBoutiqueDryClean;
use Workdo\InvestmentSystem\Events\CreateInvestorWithdrawPayment;
use Workdo\JewelleryStoreManagement\Events\CreateJewelleryStoreRepairAndCustomOrder;
use Workdo\JewelleryStoreManagement\Events\UpdateJewelleryStoreRepairAndCustomOrder;
use Workdo\JewelleryStoreManagement\Events\JewelleryStoreJewelleryBookingPayments;
use Workdo\JewelleryStoreManagement\Events\UpdateJewelleryStoreJewelleryBooking;
use Workdo\TiffinServiceManager\Events\CreateSubscriber;
use Workdo\TiffinServiceManager\Events\UpdateSubscriber;
use Workdo\DJAndOrchestraManagement\Events\UpdateDJAndOrchestraContract;
use Workdo\DietAndNutritionConsultant\Events\UpdateDietAppointment;
use Workdo\RadiologyManagement\Events\UpdateRadiologyPayment;
use Workdo\TiffinServiceManager\Events\RenewTiffinSubscriberHistory;
use Workdo\Taskly\Events\PostProjectPayment;
use Workdo\Taskly\Events\CreateProjectPayment;
use Workdo\Taskly\Events\UpdateProjectPayment;
use Workdo\GarageManagement\Events\CreateGaragePayment;
use Workdo\RepairManagementSystem\Events\MakeRepairInvoicePayment;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Add your event listeners here
        DefaultData::class => [
            DataDefault::class,
        ],
        GivePermissionToRole::class => [
            GiveRoleToPermission::class,
        ],
        PostPurchaseInvoice::class => [
            PostPurchaseInvoiceListener::class,
        ],
        PostSalesInvoice::class => [
            PostSalesInvoiceListener::class,
        ],
        CreateTransfer::class => [
            CreateTransferListener::class,
        ],
        DestroyTransfer::class => [
            DestroyTransferListener::class,
        ],
        ApprovePurchaseReturn::class => [
            CreateDebitNoteFromReturn::class,
        ],
        ApproveSalesReturn::class => [
            CreateCreditNoteFromReturn::class,
        ],
        UpdateRetainerPaymentStatus::class => [
            UpdateRetainerPaymentStatusListener::class,
        ],
        ConvertSalesRetainer::class => [
            ConvertSalesRetainerListener::class,
        ],
        CreateCommissionPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateCommissionPaymentStatus::class => [
            UpdateCommissionPaymentStatusListener::class,
        ],
        PaySalary::class => [
            PaySalaryListener::class,
        ],
        CreatePos::class => [
            BankAccountFieldUpdate::class,
            CreatePosListener::class,
        ],
        ApprovePosReturn::class => [
            ApprovePosReturnListener::class,
        ],
        CreateMobileServicePayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateMobileServicePaymentStatus::class => [
            UpdateMobileServicePaymentStatusLis::class,
        ],
        CraeteFleetBookingPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        MarkFleetBookingPaymentPaid::class => [
            MarkFleetBookingPaymentPaidListener::class,
        ],
        BeautyBookingPayments::class => [
            BeautyBookingPaymentListener::class,
        ],
        MarkBeautyBookingPaymentPaid::class => [
            MarkBeautyBookingPaymentPaidListener::class,
        ],
        CreateDairyCattlePayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateDairyCattleExpenseTracking::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateDairyCattleExpenseTracking::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateDairyCattlePaymentStatus::class => [
            UpdateDairyCattlePaymentStatusListener::class,
        ],
        UpdateDairyCattleExpenseTrackingStatus::class => [
            UpdateDairyCattleExpenseTrackingStatusListener::class,
        ],
        CreateCateringOrderPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateCateringOrderPaymentStatus::class => [
            UpdateCateringOrderPaymentStatusListener::class,
        ],
        MarkCateringExpenseTrackingAsPaid::class => [
            MarkCateringExpenseTrackingAsPaidListener::class,
        ],
        CreatePropertyPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreatePayroll::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdatePayroll::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateSalesAgentCommissionPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateSalesAgentCommissionPaymentStatus::class => [
            UpdateSalesAgentCommissionPaymentStatusLis::class,
        ],
        ApproveSalesAgentCommissionAdjustment::class => [
            ApproveSalesAgentCommissionAdjustmentLis::class,
        ],
        CreateFleetExpense::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateFleetExpense::class => [
            BankAccountFieldUpdate::class,
        ],
        PostFleetExpense::class => [
            PostFleetExpenseListener::class,
        ],
        CreateCourierPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateCourierPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateMembershipPlanPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        MembershipPlanAssigned::class => [
            MembershipPlanAssignedListener::class,
        ],
        CreateCaseExpense::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateCaseExpense::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateParkingPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        ParkingBookingPayments::class => [
            ParkingBookingPaymentsListener::class,
        ],
        UpdateParkingPaymentStatus::class => [
            UpdateParkingPaymentStatusListener::class,
        ],
        CreateLaundryPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateLaundryExpense::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateLaundryExpense::class => [
            BankAccountFieldUpdate::class,
            ],
        UpdateLaundryExpenseStatus::class => [
            UpdateLaundryExpenseStatusListener::class,
        ],
        UpdateLaundryPaymentStatus::class => [
            UpdateLaundryPaymentStatusListener::class,
        ],
        LaundryBookingPayments::class => [
            LaundryBookingPaymentsListener::class,
        ],
        CreateSocietyMaintenanceBillPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateEventBookingPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        EventBookingPayments::class => [
            EventBookingPaymentsListener::class,
        ],
        UpdateEventBookingPaymentStatus::class => [
            UpdateEventBookingPaymentStatusListener::class,
        ],
        CreateMedicalOrderPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        ApproveMedicalOrderPayment::class => [
            ApproveMedicalOrderPaymentListener::class,
        ],
        CreateBeautyBookingPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateFeeReceive::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateFeeReceive::class => [
            BankAccountFieldUpdate::class,
        ],
        MarkFeeReceiveAsCleared::class => [
            MarkFeeReceiveAsClearedListener::class,
        ],
        MarkCaseExpenseAsPaid::class => [
            MarkCaseExpenseAsPaidListner::class,
        ],
        CreateCaseExpense::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateCaseExpense::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateRentalMaintenance::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateRentalMaintenance::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateWarrantyClaim::class => [
            BankAccountFieldUpdate::class,
        ],
        CompleteWarrantyClaim::class => [
            CompleteWarrantyClaimListener::class,
        ],
        CreateVehicleBookingPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        VehicleBookingPayments::class => [
            VehicleBookingPaymentsListener::class,
        ],
        UpdateVehicleBookingPaymentStatus::class => [
            UpdateVehicleBookingPaymentStatusListener::class,
        ],
        CreateCateringExpenseTracking::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateCateringExpenseTracking::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateHolidayzRoomBooking::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateHolidayzRoomBooking::class => [
            BankAccountFieldUpdate::class,
        ],
        HolidayzBookingPayments::class => [
            HolidayzBookingPaymentsListener::class,
        ],
        ApproveHolidayzRoomBooking::class => [
            ApproveHolidayzRoomBookingListener::class,
        ],
        CreatePettyCash::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdatePettyCash::class => [
            BankAccountFieldUpdate::class,
        ],
        ApprovePettyCash::class => [
            ApprovePettyCashListener::class,
        ],
        CreatePosBillingCounter::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdatePosBillingCounter::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateCourierPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateCourierPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateVehicletradePayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateServiceHistory::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateServiceHistory::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateEyewearOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateEyewearOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateMovieFoodOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateMovieFoodOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateSecurityPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateTailorPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateLockerPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateConsultancyPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateMovieBookingPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateDietMemberPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateDietAppointment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateDJOrchestraEventPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateDJAndOrchestraContract::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateGrantApplicationPayment::class => [
            BankAccountFieldUpdate::class,
        ], 
        CreateOfficeMaintenanceLog::class => [
            BankAccountFieldUpdate::class,
        ], 
        UpdateOfficeMaintenanceLog::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateElderlyCareRequest::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateElderlyCareRequest::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateGameRental::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateGameRental::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateGameMembershipPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateGameFoodOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateGameFoodOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateLibraryBookFinePayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateNgoDonation::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateNgoDonation::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateNgoFundUtilization::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateNgoFundUtilization::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateFranchiseAgreement::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateFranchiseAgreement::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateFranchiseProfit::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateFranchiseProfit::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateMachineMaintenanceRecord::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateMachineMaintenanceRecord::class => [
            BankAccountFieldUpdate::class,
        ],
        CreatePressOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdatePressOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        CreatePressExpense::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdatePressExpense::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateBakeryExpense::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateBakeryExpense::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateBakeryStoreOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateBakeryStoreOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateCoworkingMembership::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateCoworkingMembership::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateCoworkingBooking::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateCoworkingBooking::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateMusicPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateMusicInstrumentMaintenance::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateMusicInstrumentMaintenance::class => [
            BankAccountFieldUpdate::class,
        ],
        AssignSportsClubMembership::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateSportsClubAndGroundOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateSportsClubAndGroundOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateArtWorkOrderPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateArtWorkOrderPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateEquipmentRentalRepair::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateEquipmentRentalRepair::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateEquipmentRentalBookingPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateInfluencerMarketingPayoutPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateDanceFee::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateWaterParkPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateWaterParkClothingSales::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateWaterParkClothingSales::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateWaterParkMaintenance::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateWaterParkMaintenance::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateTattooPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateBloodRequestPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateSolarHubMaintenance::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateSolarHubMaintenance::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateSolarHubPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateTvStudioOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateTvStudioOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateNewspaperAdvertisement::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateNewspaperAdvertisement::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateNewspaperSubscription::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateNewspaperSubscription::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateNewspaperPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateVehicleWashBookingPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateCctvOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateCctvOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateHairCarePayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreatePetCareBookingPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreatePetCareMembershipPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        PostPetCareAdoptionRequest::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateBoutiquePayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateBoutiqueDamage::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateBoutiqueDryClean::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateInvestorWithdrawPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateJewelleryStoreRepairAndCustomOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateJewelleryStoreRepairAndCustomOrder::class => [
            BankAccountFieldUpdate::class,
        ],
        JewelleryStoreJewelleryBookingPayments::class => [
            BankAccountFieldUpdate::class,
        ], 
        UpdateJewelleryStoreJewelleryBooking::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateSubscriber::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateSubscriber::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateDJAndOrchestraContract::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateDietAppointment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateRadiologyPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        RenewTiffinSubscriberHistory::class => [
            BankAccountFieldUpdate::class,
        ],
        PostProjectPayment::class => [
            PostProjectPaymentListener::class,
        ],
        CreateProjectPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        UpdateProjectPayment::class => [
            BankAccountFieldUpdate::class,
        ],
        CreateGaragePayment::class => [
            BankAccountFieldUpdate::class,
        ],
        MakeRepairInvoicePayment::class => [
            BankAccountFieldUpdate::class,
        ],
    ];
}
