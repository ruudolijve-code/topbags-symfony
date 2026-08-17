<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Repair\Entity\DamageReport;
use App\Repair\Form\DamageReportImageType;
use App\Repair\Service\DamageReportImageUploader;
use App\Repair\Service\DamageReportMailer;
use App\Repair\Service\DamageReportNumberGenerator;
use App\Shop\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;

final class DamageReportCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly DamageReportNumberGenerator $numberGenerator,
        private readonly DamageReportImageUploader $imageUploader,
        private readonly DamageReportMailer $damageReportMailer,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return DamageReport::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Schaderapport')
            ->setEntityLabelInPlural('Schaderapporten')
            ->setPageTitle(Crud::PAGE_INDEX, 'Schaderapporten')
            ->setPageTitle(Crud::PAGE_NEW, 'Nieuw schaderapport')
            ->setPageTitle(Crud::PAGE_EDIT, 'Schaderapport bewerken')
            ->setDefaultSort([
                'reportDate' => 'DESC',
                'id' => 'DESC',
            ])
            ->setSearchFields([
                'reportNumber',
                'customerName',
                'customerEmail',
                'brand',
                'model',
                'airline',
                'pirNumber',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $pdfAction = Action::new(
            'damageReportPdf',
            'PDF bekijken',
            'fa fa-file-pdf'
        )
            ->linkToRoute(
                'admin_damage_report_pdf',
                static fn (DamageReport $report): array => [
                    'id' => $report->getId(),
                ]
            )
            ->setHtmlAttributes([
                'target' => '_blank',
            ]);

        $mailAction = Action::new(
            'sendDamageReport',
            'Mail rapport',
            'fa fa-envelope'
        )
            ->linkToCrudAction('sendDamageReport')
            ->displayIf(
                static fn (DamageReport $report): bool =>
                    $report->getStatus() === DamageReport::STATUS_FINAL
            );

        return $actions
            ->add(Crud::PAGE_INDEX, $pdfAction)
            ->add(Crud::PAGE_EDIT, $pdfAction)
            ->add(Crud::PAGE_DETAIL, $pdfAction)
            ->add(Crud::PAGE_INDEX, $mailAction)
            ->add(Crud::PAGE_EDIT, $mailAction)
            ->add(Crud::PAGE_DETAIL, $mailAction);
    }

    public function configureFields(string $pageName): iterable
    {
        /*
         * Rapport
         */
        yield FormField::addPanel('Rapport');

        yield AssociationField::new('order', 'Opdracht / order')
            ->setFormTypeOption(
                'choice_label',
                static function (Order $order): string {
                    return sprintf(
                        '%s — %s — %s',
                        $order->getOrderNumber(),
                        $order->getCustomerEmail(),
                        $order->getCreatedAt()->format('d-m-Y'),
                    );
                }
            )
            ->formatValue(
                static function ($value): string {
                    if (!$value instanceof Order) {
                        return '';
                    }

                    return sprintf(
                        '%s — %s',
                        $value->getOrderNumber(),
                        $value->getCustomerEmail(),
                    );
                }
            )
            ->setHelp(
                'Koppel het schaderapport aan de bestelling van de schaderapport-dienst.'
            )
            ->setColumns(6);

        yield TextField::new('reportNumber', 'Rapportnummer')
            ->setHelp('Wordt automatisch aangemaakt bij het eerste opslaan.')
            ->setDisabled()
            ->hideWhenCreating()
            ->setColumns(3);

        yield DateField::new('reportDate', 'Rapportdatum')
            ->setColumns(3);

        yield ChoiceField::new('status', 'Status')
            ->setChoices(DamageReport::STATUS_CHOICES)
            ->renderAsBadges([
                DamageReport::STATUS_DRAFT => 'warning',
                DamageReport::STATUS_FINAL => 'success',
            ])
            ->setColumns(3);

        /*
         * Klant
         */
        yield FormField::addPanel('Klantgegevens');

        yield TextField::new('customerName', 'Naam klant')
            ->setColumns(6);

        yield EmailField::new('customerEmail', 'E-mail')
            ->setColumns(6);

        yield TextField::new('customerAddress', 'Adres')
            ->hideOnIndex()
            ->setColumns(6);

        yield TextField::new('customerPostalCode', 'Postcode')
            ->hideOnIndex()
            ->setColumns(3);

        yield TextField::new('customerCity', 'Woonplaats')
            ->hideOnIndex()
            ->setColumns(3);

        yield TelephoneField::new('customerPhone', 'Telefoon')
            ->hideOnIndex()
            ->setColumns(6);

        /*
         * Bagage
         */
        yield FormField::addPanel('Bagage');

        yield TextField::new('brand', 'Merk')
            ->setColumns(4);

        yield TextField::new('series', 'Serie')
            ->hideOnIndex()
            ->setColumns(4);

        yield TextField::new('model', 'Model / type')
            ->setColumns(4);

        yield TextField::new('color', 'Kleur')
            ->hideOnIndex()
            ->setColumns(4);

        yield TextField::new('dimensions', 'Afmetingen')
            ->setHelp('Bijvoorbeeld: 75 × 51 × 30 cm')
            ->hideOnIndex()
            ->setColumns(4);

        yield TextField::new(
            'estimatedPurchaseDate',
            'Geschatte aankoopdatum'
        )
            ->setHelp('Bijvoorbeeld: 2017 of circa 8–10 jaar geleden')
            ->hideOnIndex()
            ->setColumns(4);

        yield MoneyField::new(
            'estimatedPurchasePrice',
            'Geschatte aankoopprijs'
        )
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnIndex()
            ->setColumns(4);

        /*
         * Vlucht
         */
        yield FormField::addPanel('Vluchtgegevens');

        yield TextField::new('airline', 'Luchtvaartmaatschappij')
            ->setColumns(4);

        yield TextField::new('flightNumber', 'Vluchtnummer')
            ->hideOnIndex()
            ->setColumns(4);

        yield DateField::new('flightDate', 'Vluchtdatum')
            ->hideOnIndex()
            ->setColumns(4);

        yield TextField::new('airport', 'Luchthaven / plaats schade')
            ->hideOnIndex()
            ->setColumns(6);

        yield TextField::new('pirNumber', 'PIR-nummer')
            ->setColumns(6);

        /*
         * Schade
         */
        yield FormField::addPanel('Schade en beoordeling');

        yield TextareaField::new(
            'damageDescription',
            'Omschrijving schade'
        )
            ->setNumOfRows(5)
            ->setColumns(12);

        yield ChoiceField::new(
            'assessment',
            'Technische conclusie'
        )
            ->setChoices(DamageReport::ASSESSMENT_CHOICES)
            ->renderExpanded()
            ->hideOnIndex()
            ->setColumns(12);

        yield TextareaField::new(
            'technicalAssessment',
            'Technische beoordeling'
        )
            ->setNumOfRows(5)
            ->hideOnIndex()
            ->setColumns(12);

        yield TextareaField::new(
            'conclusion',
            'Conclusie rapport'
        )
            ->setNumOfRows(5)
            ->hideOnIndex()
            ->setColumns(12);

        yield MoneyField::new(
            'repairCosts',
            'Reparatiekosten'
        )
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnIndex()
            ->setColumns(4);

        yield MoneyField::new(
            'replacementValue',
            'Vervangingswaarde'
        )
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnIndex()
            ->setColumns(4);

        yield TextField::new('assessorName', 'Beoordeeld door')
            ->hideOnIndex()
            ->setColumns(4);

        /*
         * Foto's
         */
        yield FormField::addPanel('Schadefoto’s');

        yield CollectionField::new('images', 'Foto’s')
            ->setEntryType(DamageReportImageType::class)
            ->allowAdd()
            ->allowDelete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Voeg maximaal 4 duidelijke foto’s van de schade toe.')
            ->hideOnIndex()
            ->setColumns(12);

        /*
         * Intern
         */
        yield FormField::addPanel('Intern');

        yield TextareaField::new(
            'internalNotes',
            'Interne notities'
        )
            ->setHelp(
                'Deze tekst wordt niet opgenomen in het PDF-rapport.'
            )
            ->hideOnIndex()
            ->setColumns(12);

        yield DateTimeField::new('createdAt', 'Aangemaakt')
            ->onlyOnDetail();

        yield DateTimeField::new('updatedAt', 'Gewijzigd')
            ->onlyOnDetail();
    }

    public function persistEntity(
        EntityManagerInterface $entityManager,
        $entityInstance
    ): void {
        if (!$entityInstance instanceof DamageReport) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        /*
         * Vul alleen lege klantvelden vanuit de gekoppelde order.
         */
        $this->fillCustomerFromOrder($entityInstance);

        /*
         * Rapportnummer bestaat pas bij eerste opslag.
         */
        if ($entityInstance->getReportNumber() === '') {
            $entityInstance->setReportNumber(
                $this->numberGenerator->generate(
                    $entityInstance->getReportDate()
                )
            );
        }

        /*
         * Eerst opslaan zodat er een rapport-ID beschikbaar is voor:
         * public/uploads/damage-reports/{id}/
         */
        parent::persistEntity(
            $entityManager,
            $entityInstance
        );

        $this->processImageUploads($entityInstance);

        $entityManager->flush();
    }

    public function updateEntity(
        EntityManagerInterface $entityManager,
        $entityInstance
    ): void {
        if (!$entityInstance instanceof DamageReport) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        /*
         * Bewust geen fillCustomerFromOrder():
         * handmatig gecorrigeerde rapportgegevens mogen bij bewerken
         * niet opnieuw vanuit de order worden overschreven.
         */
        $this->processImageUploads($entityInstance);

        parent::updateEntity(
            $entityManager,
            $entityInstance
        );
    }

    public function sendDamageReport(
        AdminContext $context
    ): Response {
        $report = $context
            ->getEntity()
            ->getInstance();

        if (!$report instanceof DamageReport) {
            throw new \LogicException(
                'Geen geldig schaderapport geselecteerd.'
            );
        }

        try {
            $this->damageReportMailer->send($report);

            $recipient =
                $report->getCustomerEmail()
                ?: $report->getOrder()?->getCustomerEmail()
                ?: 'de klant';

            $this->addFlash(
                'success',
                sprintf(
                    'Schaderapport %s is verzonden naar %s.',
                    $report->getReportNumber(),
                    $recipient
                )
            );
        } catch (\Throwable $exception) {
            $this->addFlash(
                'danger',
                sprintf(
                    'Het schaderapport kon niet worden verzonden: %s',
                    $exception->getMessage()
                )
            );
        }

        return $this->redirect(
            $context->getReferrer()
            ?? $this->generateUrl('admin')
        );
    }

    private function processImageUploads(
        DamageReport $report
    ): void {
        $position = 0;

        foreach ($report->getImages() as $image) {
            $image->setPosition($position);

            $uploadedFile = $image->getUploadedFile();

            if ($uploadedFile !== null) {
                $oldFilename = $image->getFilename();

                /*
                 * Eerst de nieuwe foto succesvol opslaan.
                 */
                $newFilename = $this->imageUploader->upload(
                    $uploadedFile,
                    $report,
                    $position,
                );

                /*
                 * Pas daarna het oude bestand verwijderen.
                 */
                if (
                    $oldFilename !== ''
                    && $oldFilename !== $newFilename
                ) {
                    $this->imageUploader->delete(
                        $report,
                        $oldFilename,
                    );
                }

                $image->setFilename($newFilename);
                $image->setUploadedFile(null);
            }

            ++$position;
        }
    }

    private function fillCustomerFromOrder(
        DamageReport $report
    ): void {
        $order = $report->getOrder();

        if (!$order instanceof Order) {
            return;
        }

        $address = $order->getShippingAddress();

        if ($report->getCustomerName() === '') {
            $firstName = trim(
                (string) ($address['firstName'] ?? '')
            );

            $lastName = trim(
                (string) ($address['lastName'] ?? '')
            );

            $report->setCustomerName(
                trim($firstName . ' ' . $lastName)
            );
        }

        if (
            $report->getCustomerEmail() === null
            || $report->getCustomerEmail() === ''
        ) {
            $report->setCustomerEmail(
                $order->getCustomerEmail()
            );
        }

        if (
            $report->getCustomerPhone() === null
            || $report->getCustomerPhone() === ''
        ) {
            $report->setCustomerPhone(
                $order->getCustomerPhone()
            );
        }

        if (
            $report->getCustomerAddress() === null
            || $report->getCustomerAddress() === ''
        ) {
            $report->setCustomerAddress(
                isset($address['street'])
                    ? (string) $address['street']
                    : null
            );
        }

        if (
            $report->getCustomerPostalCode() === null
            || $report->getCustomerPostalCode() === ''
        ) {
            $report->setCustomerPostalCode(
                isset($address['postalCode'])
                    ? (string) $address['postalCode']
                    : null
            );
        }

        if (
            $report->getCustomerCity() === null
            || $report->getCustomerCity() === ''
        ) {
            $report->setCustomerCity(
                isset($address['city'])
                    ? (string) $address['city']
                    : null
            );
        }
    }
}