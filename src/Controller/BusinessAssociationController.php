<?php

namespace CUPPublicBusinessModule\Controller;

use BusinessCore\Entity\Employee;
use BusinessCore\Exception\EmployeeAlreadyAssociatedToDifferentBusinessException;
use BusinessCore\Exception\EmployeeAlreadyAssociatedToThisBusinessException;
use BusinessCore\Exception\EmployeeDeletedException;
use BusinessCore\Service\BusinessService;

use CUPPublicBusinessModule\Form\AssociationCodeForm;
use CUPPublicBusinessModule\Service\EmployeeService;
use Doctrine\ORM\EntityNotFoundException;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\I18n\Translator;
use Zend\View\Model\ViewModel;

class BusinessAssociationController extends AbstractActionController
{
    /**
     * @var BusinessService
     */
    private $businessService;
    /**
     * @var AssociationCodeForm
     */
    private $associationCodeForm;
    /**
     * @var Translator
     */
    private $translator;
    /**
     * @var EmployeeService
     */
    private $employeeService;

    /**
     * BusinessAssociationController constructor.
     * @param BusinessService $businessService
     * @param EmployeeService $employeeService
     * @param AssociationCodeForm $associationCodeForm
     * @param Translator $translator
     */
    public function __construct(
        BusinessService $businessService,
        EmployeeService $employeeService,
        AssociationCodeForm $associationCodeForm,
        Translator $translator
    ) {
        $this->businessService = $businessService;
        $this->associationCodeForm = $associationCodeForm;
        $this->translator = $translator;
        $this->employeeService = $employeeService;
    }

    public function businessAssociationAction()
    {
        //if there is mobile param the layout changes
        $mobile = $this->params()->fromRoute('mobile');
        if ($mobile) {
            $this->layout('layout/map');
        }

        if(is_null($this->identity())) {
            return $this->redirect()->toRoute('login');
        }

        $employeeId = $this->identity()->getId();
        $employee = $this->employeeService->getEmployeeFromId($employeeId);

        //if there is an active association
        if ($employee instanceof Employee && $employee->hasActiveBusinessAssociation()) {
            $viewModel = new ViewModel(
                [
                    'businessEmployee' => $employee->getActiveBusinessEmployee()
                ]
            );
            $viewModel->setTemplate('cup-public-business-module/business-association/business-already-associated');
            return $viewModel;
        }

        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost();

            try {

                $this->businessService->associateEmployeeToBusinessByAssociationCode($employeeId, $postData['code']);

                $this->flashMessenger()->addSuccessMessage($this->translator->translate('Operazione avvenuta con successo! Appena verrai confermato riceverai una email con le istruzioni'));
                if ($mobile){
                    return $this->redirect()->toRoute('area-utente/mobile');
                }
                return $this->redirect()->toRoute('area-utente');
            } catch (EntityNotFoundException $e) {
                $this->flashMessenger()->addErrorMessage($this->translator->translate('Il codice inserito non è valido'));
            } catch (EmployeeDeletedException $e) {
                $this->flashMessenger()->addErrorMessage($this->translator->translate('Non puoi essere associato a questa azienda'));
            } catch (EmployeeAlreadyAssociatedToThisBusinessException $e) {
                $this->flashMessenger()->addErrorMessage($this->translator->translate('Sei già associato a questa azienda'));
            } catch (EmployeeAlreadyAssociatedToDifferentBusinessException $e) {
                $this->flashMessenger()->addErrorMessage($this->translator->translate("Sei già associato ad un'altra azienda"));
            }

            return $this->redirect()->toRoute('area-utente/associate');
        }


        return new ViewModel(
            [
                'form' => $this->associationCodeForm
            ]
        );
    }
}
