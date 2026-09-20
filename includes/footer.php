<?php
/**
 * Rodapé e Scripts - AdminLTE 3 / Bootstrap 5
 * eBill Mini ERP Web
 */
$empresa = get_empresa_info();
?>
    <!-- Footer -->
    <footer class="main-footer text-sm border-top bg-white py-3">
        <div class="float-end d-none d-sm-inline">
            <span class="badge bg-primary">v1.0.0</span>
        </div>
        <strong>&copy; <?= date('Y') ?> <a href="index.php" class="text-decoration-none text-primary"><?= htmlspecialchars($empresa['nome_fantasia']) ?></a>.</strong> Todos os direitos reservados.
    </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- jQuery Mask Plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<script>
$(document).ready(function() {
    // Máscaras de Entrada de Dados (BR)
    $('.mask-cpf-cnpj').on('input focusout', function() {
        var val = $(this).val().replace(/\D/g, '');
        if (val.length <= 11) {
            $(this).mask('000.000.000-00999', {reverse: false});
        } else {
            $(this).mask('00.000.000/0000-00', {reverse: false});
        }
    }).trigger('input');

    $('.mask-phone').mask('(00) 00000-0000');
    $('.mask-cep').mask('00000-000');
    $('.mask-money').mask('#.##0,00', {reverse: true});

    // Confirmação de Exclusão Padrão
    $('.btn-delete-confirm').on('click', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var item = $(this).data('item') || 'este registro';
        
        Swal.fire({
            title: 'Tem certeza?',
            text: "Deseja realmente excluir " + item + "? Esta ação não pode ser desfeita!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-trash me-1"></i> Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });

    // Autopreenchimento de Endereço via CEP (ViaCEP API)
    $('#cep').on('blur', function() {
        var cep = $(this).val().replace(/\D/g, '');
        if (cep.length === 8) {
            $('#endereco, #bairro, #cidade, #uf').addClass('is-loading');
            $.getJSON('https://viacep.com.br/ws/' + cep + '/json/', function(dados) {
                if (!("erro" in dados)) {
                    $('#endereco').val(dados.logradouro);
                    $('#bairro').val(dados.bairro);
                    $('#cidade').val(dados.localidade);
                    $('#uf').val(dados.uf);
                    $('#numero').focus();
                } else {
                    Swal.fire('CEP não encontrado', 'Por favor, digite o endereço manualmente.', 'info');
                }
            });
        }
    });
});
</script>
</body>
</html>
