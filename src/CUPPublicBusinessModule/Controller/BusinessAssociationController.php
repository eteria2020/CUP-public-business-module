<?php

namespace CUPPublicBusinessModule\Controller;

use BusinessCore\Exception\EmployeeAlreadyAssociatedToDifferentBusinessException;
use BusinessCore\Exception\EmployeeAlreadyAssociatedToThisBusinessException;
use BusinessCore\Exception\EmployeeDeletedException;
use BusinessCore\Service\BusinessService;

use CUPPublicBusinessModule\Form\AssociationCodeForm;
use Doctrine\ORM\EntityNotFoundException;
use Zend\Http\Response;
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
     * BusinessAssociationController constructor.
     * @param BusinessService $businessService
     * @param AssociationCodeForm $associationCodeForm
     * @param Translator $translator
     */
    public function __construct(
        BusinessService $businessService,
        AssociationCodeForm $associationCodeForm,
        Translator $translator
    ) {
        $this->businessService = $businessService;
        $this->associationCodeForm = $associationCodeForm;
        $this->translator = $translator;
    }

    public function businessAssociationAction()
    {
        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost();

            try {
                $employeeId = $this->identity()->getId();
                $this->businessService->associateEmployeeToBusinessByAssociationCode($employeeId, $postData['code']);

                $this->flashMessenger()->addSuccessMessage($this->translator->translate('Operazione avvenuta con successo! Appena verrai confermato riceverai una email con le istruzioni'));
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
