<?php

namespace NsUtil\IRPF;

use DateTime;
use NsUtil\Financial\Round;
use NsUtil\Financial\Rounders\ABNT_NBR_5891;
use NsUtil\IRPF\Exception\ConfigNotFoundException;
use NsUtil\IRPF\Exception\LiquidValueNotSetted;

use function NsUtil\dd;

/**
 * Class IRRF
 *
 * Calculates the IRRF (Imposto de Renda Retido na Fonte) based on the given liquid value and the tax tables for a specific year.
 */
class IRRF
{
    /**
     * Tax tables for different years
     */
    private const TABELA = [
        202305 => [
            '4664.68' => ['aliquota' => 0.275, 'deducao' => 884.96],
            '3751.06' => ['aliquota' => 0.225, 'deducao' => 651.73],
            '2826.66' => ['aliquota' => 0.15, 'deducao' => 370.40],
            '2112.01' => ['aliquota' => 0.075, 'deducao' => 158.4],
            '0.0' => ['aliquota' => 0.0, 'deducao' => 0.0],
        ],
        201504 => [
            '4664.68' => ['aliquota' => 0.275, 'deducao' => 869.36],
            '3751.06' => ['aliquota' => 0.225, 'deducao' => 636.13],
            '2826.66' => ['aliquota' => 0.15, 'deducao' => 354.8],
            '1903.99' => ['aliquota' => 0.075, 'deducao' => 142.8],
            '0.0' => ['aliquota' => 0.0, 'deducao' => 0.0],
        ],
    ];

    private array $config;
    private float $valorLiquido;
    private float $valorBruto;
    private int $tabela;

    /**
     * IRRF constructor.
     *
     * @param DateTime $dataPagamento The payment date used to determine the tax year.
     *
     * @throws ConfigNotFoundException If the tax table for the given year is not found.
     */
    public function __construct(DateTime $dataPagamento)
    {
        $this->tabela = (int) date('Ym', $dataPagamento->getTimestamp());
        $config = array_values(
            array_filter(self::TABELA, fn ($item) => $this->tabela >= $item, ARRAY_FILTER_USE_KEY)
        );

        if (!isset($config[0])) {
            throw new ConfigNotFoundException('Tax table not found');
        }

        $this->config = $config[0];
    }

    /**
     * Calculates the IRRF based on the given liquid value.
     *
     * @return float The calculated IRRF value.
     *
     * @throws LiquidValueNotSetted If the liquid value is not set.
     */
    public function calculaIRRFBaseLiquido(): float
    {
        if (null === $this->valorLiquido) {
            throw new LiquidValueNotSetted('Liquid value not defined');
        }

        if ($this->valorLiquido === 0.0) {
            return 0.0;
        }

        $irpf = $this->calculo();

        // return $irpf;

        return Round::handle(new ABNT_NBR_5891($irpf));
    }

    /**
     * Performs the IRRF calculation.
     *
     * @param float|null $aliquota The tax rate to use in the calculation. If null, it will be determined based on the liquid value.
     *
     * @return float The calculated IRRF value.
     */
    private function calculo(float $aliquota = null): float
    {
        if (null === $aliquota) {
            extract($this->irrfEscolheTaxa($this->valorLiquido));
        } else {
            $deducao = $this->getDeducaoByAliquota($aliquota);
        }

        $irrf = ($this->valorLiquido * $aliquota - $deducao) / (1 - $aliquota);

        if ($irrf < 10) {
            return 0.0;
        } else {
            // Verify if the applied rate is correct
            $novaaliquota = self::irrfEscolheTaxa($this->valorLiquido + $irrf)['aliquota'];
            if ($novaaliquota !== $aliquota) {
                return $this->calculo($novaaliquota);
            } else {
                return $irrf;
            }
        }
    }

    /**
     * Determines the tax rate and deduction based on the liquid value.
     *
     * @return array The tax rate and deduction.
     *
     * @throws ConfigNotFoundException If the tax table for the given liquid value is not found.
     */
    private function irrfEscolheTaxa($valorLiquido): array
    {
        $configs = array_values(array_filter($this->config, fn ($valorLimite) => $valorLiquido >= (float) $valorLimite, ARRAY_FILTER_USE_KEY));
        if (!isset($configs[0])) {
            throw new ConfigNotFoundException('Tax not found');
        }

        return $configs[0];
    }

    /**
     * Retrieves the deduction value based on the tax rate.
     *
     * @param float $aliquota The tax rate.
     *
     * @return float The deduction value.
     *
     * @throws ConfigNotFoundException If the tax table for the given tax rate is not found.
     */
    private function getDeducaoByAliquota(float $aliquota): float
    {
        $filtered = array_values(
            array_filter($this->config, fn ($item) => $item['aliquota'] === $aliquota)
        );
        if (!isset($filtered[0]['deducao']) || $filtered[0]['deducao'] === null) {
            throw new ConfigNotFoundException("Tax table for aliquota $aliquota not found");
        }

        return $filtered[0]['deducao'];
    }

    /**
     * Gets the liquid value.
     *
     * @return float The liquid value.
     */
    public function getValorLiquido()
    {
        return $this->valorLiquido;
    }

    /**
     * Sets the liquid value.
     *
     * @param float|null $valorLiquido The liquid value to set.
     *
     * @return $this
     */
    public function setValorLiquido(?float $valorLiquido)
    {
        $this->valorLiquido = $valorLiquido;

        return $this;
    }

    /**
     * Gets the gross value.
     *
     * @return float The gross value.
     */
    public function getValorBruto()
    {
        return $this->valorBruto;
    }

    /**
     * Sets the gross value.
     *
     * @param float $valorBruto The gross value to set.
     *
     * @return $this
     */
    public function setValorBruto($valorBruto)
    {
        $this->valorBruto = $valorBruto;

        return $this;
    }
}
