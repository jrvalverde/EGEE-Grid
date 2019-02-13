##############################################################################
#
# Default values for variable parameters:
#
#        make opt
#        make deb
#        make opt "DDEFS=-DNO_NONBONDED"
#
##############################################################################
#
#        setenv EXECUTABLE_TYPE6v2 i386-abosft ; make deb
#
##############################################################################
#
# This Makefile relies on the environment variable HOSTTYPE
# to select the proper compiler and compiler flags.
#
##############################################################################
#
# The only Installation definitions:
#
# The directory where the binary will be installed (to be put on your path):
BINDIR=$${MODINSTALL6v2}/bin
#
# The directory where the libraires for running the program will be installed
LIBDIR=$${MODINSTALL6v2}/modlib
#
# Files to be installed into the LIB directory (some may be directories):
LIBFILES=../../modlib/* top.ini
#
# Files to be installed into the BIN directory:
BINFILES=../../scripts/*
#
# The directories with the include files:
INCLUDE=-I../include
#
# The directories with the module files
INCLUDEDIR=-I ../include -I ../commands -I ../definitions -I ../routines
#
# The recursive mkdir command:
MKDIRS=mkdir -p
#
# Program name:
PROGRAM=mod6v2
#
# Source for the program:

SOURCES=\
../definitions/dynamic_allocation_common.F90 \
../definitions/align2_common.F90 \
../definitions/align_common.F90 \
../definitions/alignment_constants.F90 \
../definitions/atmcls_common.F90 \
../definitions/charmm_constants.F90 \
../definitions/cmpcsr_common.F90 \
../definitions/dihlib_common.F90 \
../definitions/equiv_common.F90 \
../definitions/hash_common.F90 \
../definitions/intcr_common.F90 \
../definitions/io_constants.F90 \
../definitions/lib_common.F90 \
../definitions/md_common.F90 \
../definitions/mdt_dat_common.F90 \
../definitions/mdt_mdt_common.F90 \
../definitions/mdt_rd_common.F90 \
../definitions/mem_common.F90 \
../definitions/mkilst_common.F90 \
../definitions/mod_common.F90 \
../definitions/model2_common.F90 \
../definitions/model_common.F90 \
../definitions/most_constants.F90 \
../definitions/numbers_constants.F90 \
../definitions/opt_common.F90 \
../definitions/param_common.F90 \
../definitions/pdbnam_common.F90 \
../definitions/pseudo_common.F90 \
../definitions/radii_common.F90 \
../definitions/resgrp_common.F90 \
../definitions/residues_common.F90 \
../definitions/residues_constants.F90 \
../definitions/restr_common.F90 \
../definitions/sched_common.F90 \
../definitions/sigma_common.F90 \
../definitions/special_common.F90 \
../definitions/sstruc_common.F90 \
../definitions/string_constants.F90 \
../definitions/symmetry_common.F90 \
../definitions/top_common.F90 \
../definitions/topol_common.F90 \
../definitions/types_constants.F90 \
../definitions/seqsearch_common.F90 \
../definitions/restraint_constants.F90 \
../main/mod.F90 \
../main/top.F90 \
../main/top_act.F90 \
../main/top_mod.F90 \
../routines/algebra_routines.F90 \
../routines/alloc_routines.F90 \
../routines/atom_residue_routines.F90 \
../routines/check_routines.F90 \
../routines/compare_routines.F90 \
../routines/dihedral_routines.F90 \
../routines/features_routines.F90 \
../routines/fit_routines.F90 \
../routines/finish_routine.F90 \
../routines/getdata_routines.F90 \
../routines/io_routines.F90 \
../routines/mdt_routines.F90 \
../routines/nr_routines.F90 \
../routines/psa_routines.F90 \
../routines/ranlib_routines.F90 \
../routines/rdlib_routines.F90 \
../routines/string_routines.F90 \
../routines/sys_routines.F90 \
../routines/cg_routines.F90 \
../routines/md_routines.F90 \
../routines/nonb_pairs_routines.F90 \
../routines/objective_function_routines.F90 \
../routines/pseudo_atoms_routines.F90 \
../routines/switch_routines.F90 \
../routines/violations_routines.F90 \
../routines/e_binormal.F90 \
../routines/e_cosine.F90 \
../routines/e_coulomb.F90 \
../routines/e_gauss.F90 \
../routines/e_harmonic.F90 \
../routines/e_lennard.F90 \
../routines/e_spline.F90 \
../routines/e_symmetry.F90 \
../routines/r_alpha.F90 \
../routines/r_dihedral.F90 \
../routines/r_distance.F90 \
../routines/r_nonb_pair.F90 \
../routines/r_one_atom.F90 \
../routines/r_phi_psi_binormal.F90 \
../routines/r_sheet.F90 \
../routines/r_stereo.F90 \
../routines/r_strand.F90 \
../routines/r_user_distance.F90 \
../routines/restraint_routines.F90 \
../commands/optimize.F90 \
../commands/debug_function.F90 \
../commands/make_write_uhbd_lib.F90 \
../commands/quick_and_dirty.F90 \
../commands/write_alignment.F90 \
../commands/write_model.F90 \
../commands/write_restraints.F90 \
../commands/write_schedule.F90 \
../commands/write_topology_model.F90 \
../commands/read_alignment.F90 \
../commands/read_atom_classes.F90 \
../commands/read_model.F90 \
../commands/read_parameters.F90 \
../commands/read_restraints.F90 \
../commands/read_restyp_lib.F90 \
../commands/read_schedule.F90 \
../commands/read_topology.F90 \
../commands/rotate_model.F90 \
../commands/build_model.F90 \
../commands/describe.F90 \
../commands/generate_topology.F90 \
../commands/iupac_model.F90 \
../commands/make_topology_model.F90 \
../commands/mutate_model.F90 \
../commands/orient_model.F90 \
../commands/patch.F90 \
../commands/patch_ss_model.F90 \
../commands/patch_ss_templates.F90 \
../commands/principal_components.F90 \
../commands/randomize_xyz.F90 \
../commands/reindex_restraints.F90 \
../commands/rename_segments.F90 \
../commands/reorder_atoms.F90 \
../commands/rotate_dihedrals.F90 \
../commands/transfer_xyz.F90 \
../commands/unbuild_model.F90 \
../commands/align.F90 \
../commands/align2d.F90 \
../commands/align3d.F90 \
../commands/align_consensus.F90 \
../commands/check_alignment.F90 \
../commands/color_aln_model.F90 \
../commands/compare.F90 \
../commands/compare_alignments.F90 \
../commands/delete_alignment.F90 \
../commands/dendrogram.F90 \
../commands/expand_alignment.F90 \
../commands/id_table.F90 \
../commands/malign.F90 \
../commands/malign3d.F90 \
../commands/segment_matching.F90 \
../commands/sequence_comparison.F90 \
../commands/sequence_search.F90 \
../commands/sequence_to_ali.F90 \
../commands/superpose.F90 \
../commands/transfer_res_numb.F90 \
../commands/energy.F90 \
../commands/add_restraint.F90 \
../commands/condense_restraints.F90 \
../commands/define_symmetry.F90 \
../commands/delete_restraint.F90 \
../commands/make_region.F90 \
../commands/make_restraints.F90 \
../commands/make_schedule.F90 \
../commands/pick_atoms.F90 \
../commands/pick_hot_atoms.F90 \
../commands/pick_restraints.F90 \
../commands/write_pdb_xref.F90 \
../commands/spline_restraints.F90

OBJECTS=${SOURCES:.F90=.o}

include Makefile.config

# DO NOT DELETE THIS LINE - used by make depend
../commands/add_restraint.o: algebra_routines.mod atom_residue_routines.mod
../commands/add_restraint.o: check_routines.mod io_routines.mod
../commands/align.o: compare_routines.mod io_routines.mod
../commands/align2d.o: algebra_routines.mod atom_residue_routines.mod
../commands/align2d.o: compare_routines.mod getdata_routines.mod io_routines.mod
../commands/align3d.o: algebra_routines.mod atom_residue_routines.mod
../commands/align3d.o: compare_routines.mod fit_routines.mod
../commands/align3d.o: getdata_routines.mod io_routines.mod
../commands/align3d.o: numbers_constants.mod string_routines.mod
../commands/align_consensus.o: compare_routines.mod io_routines.mod
../commands/align_consensus.o: string_constants.mod string_routines.mod
../commands/build_model.o: algebra_routines.mod atom_residue_routines.mod
../commands/build_model.o: io_routines.mod numbers_constants.mod
../commands/build_model.o: ranlib_routines.mod restraint_routines.mod
../commands/check_alignment.o: algebra_routines.mod atom_residue_routines.mod
../commands/check_alignment.o: compare_routines.mod fit_routines.mod
../commands/check_alignment.o: getdata_routines.mod io_routines.mod
../commands/check_alignment.o: numbers_constants.mod
../commands/color_aln_model.o: atom_residue_routines.mod io_routines.mod
../commands/compare.o: algebra_routines.mod atom_residue_routines.mod
../commands/compare.o: compare_routines.mod dihedral_routines.mod
../commands/compare.o: fit_routines.mod getdata_routines.mod io_routines.mod
../commands/compare.o: residues_common.mod string_routines.mod
../commands/compare.o: types_constants.mod
../commands/compare_alignments.o: io_routines.mod
../commands/condense_restraints.o: io_routines.mod restraint_routines.mod
../commands/condense_restraints.o: types_constants.mod
../commands/debug_function.o: algebra_routines.mod io_routines.mod
../commands/debug_function.o: nonb_pairs_routines.mod numbers_constants.mod
../commands/debug_function.o: objective_function_routines.mod
../commands/define_symmetry.o: io_routines.mod
../commands/delete_alignment.o: getdata_routines.mod most_constants.mod
../commands/delete_restraint.o: atom_residue_routines.mod io_routines.mod
../commands/delete_restraint.o: restraint_routines.mod
../commands/dendrogram.o: algebra_routines.mod alloc_routines.mod
../commands/dendrogram.o: io_routines.mod string_constants.mod
../commands/dendrogram.o: string_routines.mod
../commands/describe.o: algebra_routines.mod atom_residue_routines.mod
../commands/describe.o: compare_routines.mod getdata_routines.mod
../commands/describe.o: io_constants.mod io_routines.mod numbers_constants.mod
../commands/describe.o: residues_common.mod types_constants.mod
../commands/energy.o: algebra_routines.mod atom_residue_routines.mod
../commands/energy.o: check_routines.mod e_binormal_module.mod
../commands/energy.o: e_gauss_module.mod features_routines.mod io_routines.mod
../commands/energy.o: numbers_constants.mod objective_function_routines.mod
../commands/energy.o: optimize_module.mod restraint_routines.mod
../commands/energy.o: string_routines.mod types_constants.mod
../commands/energy.o: violations_routines.mod
../commands/expand_alignment.o: io_routines.mod
../commands/generate_topology.o: atom_residue_routines.mod getdata_routines.mod
../commands/generate_topology.o: io_routines.mod patch_module.mod
../commands/generate_topology.o: residues_common.mod string_constants.mod
../commands/generate_topology.o: string_routines.mod
../commands/id_table.o: compare_routines.mod io_routines.mod
../commands/iupac_model.o: algebra_routines.mod atom_residue_routines.mod
../commands/iupac_model.o: dihedral_routines.mod io_routines.mod
../commands/iupac_model.o: types_constants.mod
../commands/make_region.o: algebra_routines.mod io_routines.mod
../commands/make_region.o: ranlib_routines.mod
../commands/make_restraints.o: algebra_routines.mod atom_residue_routines.mod
../commands/make_restraints.o: delete_restraint_module.mod io_routines.mod
../commands/make_restraints.o: mdt_routines.mod mod_common.mod
../commands/make_restraints.o: r_alpha_module.mod r_dihedral_module.mod
../commands/make_restraints.o: r_distance_module.mod r_nonb_pair_module.mod
../commands/make_restraints.o: r_one_atom_module.mod
../commands/make_restraints.o: r_phi_psi_binormal_module.mod r_sheet_module.mod
../commands/make_restraints.o: r_stereo_module.mod r_strand_module.mod
../commands/make_restraints.o: r_user_distance_module.mod restraint_routines.mod
../commands/make_restraints.o: string_routines.mod
../commands/make_schedule.o: io_routines.mod read_schedule_module.mod
../commands/make_schedule.o: string_constants.mod
../commands/make_topology_model.o: atom_residue_routines.mod io_routines.mod
../commands/make_topology_model.o: numbers_constants.mod string_constants.mod
../commands/make_topology_model.o: string_routines.mod
../commands/make_write_uhbd_lib.o: atom_residue_routines.mod io_routines.mod
../commands/malign.o: atom_residue_routines.mod compare_routines.mod
../commands/malign.o: io_routines.mod rdlib_routines.mod residues_common.mod
../commands/malign3d.o: algebra_routines.mod atom_residue_routines.mod
../commands/malign3d.o: compare_routines.mod fit_routines.mod
../commands/malign3d.o: getdata_routines.mod io_routines.mod
../commands/malign3d.o: numbers_constants.mod string_constants.mod
../commands/malign3d.o: string_routines.mod write_model_module.mod
../commands/mutate_model.o: atom_residue_routines.mod io_routines.mod
../commands/optimize.o: cg_routines.mod io_routines.mod md_routines.mod
../commands/optimize.o: objective_function_routines.mod string_routines.mod
../commands/orient_model.o: algebra_routines.mod fit_routines.mod
../commands/orient_model.o: io_routines.mod
../commands/patch.o: algebra_routines.mod atom_residue_routines.mod
../commands/patch.o: charmm_constants.mod dihedral_routines.mod io_routines.mod
../commands/patch.o: most_constants.mod numbers_constants.mod
../commands/patch.o: residues_common.mod special_common.mod string_routines.mod
../commands/patch_ss_model.o: atom_residue_routines.mod describe_module.mod
../commands/patch_ss_model.o: dihedral_routines.mod io_routines.mod
../commands/patch_ss_model.o: patch_module.mod
../commands/patch_ss_templates.o: algebra_routines.mod atom_residue_routines.mod
../commands/patch_ss_templates.o: compare_routines.mod describe_module.mod
../commands/patch_ss_templates.o: dihedral_routines.mod getdata_routines.mod
../commands/patch_ss_templates.o: io_routines.mod patch_module.mod
../commands/pick_atoms.o: algebra_routines.mod atom_residue_routines.mod
../commands/pick_atoms.o: io_routines.mod restraint_routines.mod
../commands/pick_atoms.o: string_routines.mod
../commands/pick_hot_atoms.o: algebra_routines.mod atom_residue_routines.mod
../commands/pick_hot_atoms.o: io_routines.mod numbers_constants.mod
../commands/pick_hot_atoms.o: objective_function_routines.mod
../commands/pick_hot_atoms.o: optimize_module.mod pick_atoms_module.mod
../commands/pick_hot_atoms.o: restraint_routines.mod string_routines.mod
../commands/pick_hot_atoms.o: violations_routines.mod
../commands/pick_restraints.o: algebra_routines.mod check_routines.mod
../commands/pick_restraints.o: io_routines.mod restraint_routines.mod
../commands/principal_components.o: algebra_routines.mod alloc_routines.mod
../commands/principal_components.o: io_routines.mod numbers_constants.mod
../commands/principal_components.o: string_constants.mod
../commands/randomize_xyz.o: algebra_routines.mod io_routines.mod
../commands/randomize_xyz.o: ranlib_routines.mod
../commands/read_alignment.o: alignment_constants.mod atom_residue_routines.mod
../commands/read_alignment.o: compare_routines.mod io_routines.mod
../commands/read_alignment.o: read_model_module.mod residues_common.mod
../commands/read_alignment.o: string_constants.mod string_routines.mod
../commands/read_atom_classes.o: io_routines.mod string_routines.mod
../commands/read_model.o: algebra_routines.mod atom_residue_routines.mod
../commands/read_model.o: io_routines.mod residues_common.mod
../commands/read_model.o: string_constants.mod string_routines.mod
../commands/read_parameters.o: atom_residue_routines.mod charmm_constants.mod
../commands/read_parameters.o: io_routines.mod read_restraints_module.mod
../commands/read_parameters.o: string_constants.mod string_routines.mod
../commands/read_restraints.o: algebra_routines.mod atom_residue_routines.mod
../commands/read_restraints.o: check_routines.mod e_spline_module.mod
../commands/read_restraints.o: io_routines.mod spline_restraints_module.mod
../commands/read_restraints.o: string_constants.mod string_routines.mod
../commands/read_restraints.o: types_constants.mod
../commands/read_restyp_lib.o: atom_residue_routines.mod io_routines.mod
../commands/read_restyp_lib.o: most_constants.mod residues_common.mod
../commands/read_restyp_lib.o: string_routines.mod
../commands/read_schedule.o: io_routines.mod string_routines.mod
../commands/read_topology.o: atom_residue_routines.mod io_routines.mod
../commands/read_topology.o: string_constants.mod string_routines.mod
../commands/reindex_restraints.o: atom_residue_routines.mod io_routines.mod
../commands/rename_segments.o: algebra_routines.mod io_routines.mod
../commands/reorder_atoms.o: algebra_routines.mod atom_residue_routines.mod
../commands/reorder_atoms.o: dihedral_routines.mod io_routines.mod
../commands/reorder_atoms.o: string_routines.mod
../commands/rotate_dihedrals.o: algebra_routines.mod dihedral_routines.mod
../commands/rotate_dihedrals.o: io_routines.mod numbers_constants.mod
../commands/rotate_dihedrals.o: ranlib_routines.mod residues_common.mod
../commands/rotate_dihedrals.o: string_routines.mod types_constants.mod
../commands/rotate_model.o: algebra_routines.mod
../commands/segment_matching.o: alignment_constants.mod io_routines.mod
../commands/segment_matching.o: mdt_routines.mod most_constants.mod
../commands/segment_matching.o: rdlib_routines.mod residues_common.mod
../commands/segment_matching.o: string_constants.mod write_alignment_module.mod
../commands/sequence_comparison.o: algebra_routines.mod
../commands/sequence_comparison.o: atom_residue_routines.mod
../commands/sequence_comparison.o: compare_routines.mod io_routines.mod
../commands/sequence_comparison.o: numbers_constants.mod rdlib_routines.mod
../commands/sequence_comparison.o: residues_common.mod string_routines.mod
../commands/sequence_search.o: algebra_routines.mod alignment_constants.mod
../commands/sequence_search.o: atom_residue_routines.mod compare_routines.mod
../commands/sequence_search.o: io_routines.mod ranlib_routines.mod
../commands/sequence_search.o: rdlib_routines.mod read_alignment_module.mod
../commands/sequence_search.o: residues_common.mod string_constants.mod
../commands/sequence_search.o: string_routines.mod write_alignment_module.mod
../commands/sequence_to_ali.o: atom_residue_routines.mod
../commands/sequence_to_ali.o: delete_alignment_module.mod io_routines.mod
../commands/sequence_to_ali.o: residues_common.mod
../commands/spline_restraints.o: algebra_routines.mod check_routines.mod
../commands/spline_restraints.o: e_spline_module.mod io_routines.mod
../commands/spline_restraints.o: numbers_constants.mod
../commands/spline_restraints.o: objective_function_routines.mod
../commands/spline_restraints.o: restraint_routines.mod string_routines.mod
../commands/spline_restraints.o: types_constants.mod
../commands/superpose.o: algebra_routines.mod atom_residue_routines.mod
../commands/superpose.o: check_routines.mod fit_routines.mod io_routines.mod
../commands/superpose.o: string_routines.mod write_model_module.mod
../commands/transfer_res_numb.o: atom_residue_routines.mod check_routines.mod
../commands/transfer_res_numb.o: compare_routines.mod io_routines.mod
../commands/transfer_xyz.o: algebra_routines.mod atom_residue_routines.mod
../commands/transfer_xyz.o: dendrogram_module.mod getdata_routines.mod
../commands/transfer_xyz.o: io_constants.mod io_routines.mod
../commands/transfer_xyz.o: numbers_constants.mod string_routines.mod
../commands/unbuild_model.o: numbers_constants.mod
../commands/write_alignment.o: alignment_constants.mod atom_residue_routines.mod
../commands/write_alignment.o: compare_routines.mod io_routines.mod
../commands/write_alignment.o: read_alignment_module.mod residues_common.mod
../commands/write_alignment.o: string_constants.mod string_routines.mod
../commands/write_model.o: algebra_routines.mod atom_residue_routines.mod
../commands/write_model.o: io_routines.mod most_constants.mod
../commands/write_model.o: string_routines.mod sys_routines.mod
../commands/write_pdb_xref.o: atom_residue_routines.mod io_routines.mod
../commands/write_pdb_xref.o: string_constants.mod string_routines.mod
../commands/write_restraints.o: atom_residue_routines.mod io_routines.mod
../commands/write_restraints.o: numbers_constants.mod string_constants.mod
../commands/write_restraints.o: string_routines.mod types_constants.mod
../commands/write_schedule.o: io_routines.mod
../commands/write_topology_model.o: io_routines.mod
../definitions/align2_common.o: alignment_constants.mod string_constants.mod
../definitions/align_common.o: alignment_constants.mod string_constants.mod
../definitions/atmcls_common.o: string_constants.mod
../definitions/cmpcsr_common.o: most_constants.mod residues_constants.mod
../definitions/cmpcsr_common.o: string_constants.mod
../definitions/dihlib_common.o: most_constants.mod residues_constants.mod
../definitions/dihlib_common.o: string_constants.mod
../definitions/equiv_common.o: most_constants.mod string_constants.mod
../definitions/lib_common.o: most_constants.mod string_constants.mod
../definitions/mdt_dat_common.o: most_constants.mod residues_constants.mod
../definitions/mdt_dat_common.o: string_constants.mod
../definitions/mdt_mdt_common.o: most_constants.mod string_constants.mod
../definitions/mdt_rd_common.o: most_constants.mod
../definitions/mem_common.o: most_constants.mod string_constants.mod
../definitions/mod_common.o: align2_common.mod align_common.mod
../definitions/mod_common.o: alignment_constants.mod atmcls_common.mod
../definitions/mod_common.o: charmm_constants.mod cmpcsr_common.mod
../definitions/mod_common.o: dihlib_common.mod dynamic_allocation_common.mod
../definitions/mod_common.o: equiv_common.mod hash_common.mod intcr_common.mod
../definitions/mod_common.o: io_constants.mod lib_common.mod md_common.mod
../definitions/mod_common.o: mdt_dat_common.mod mdt_mdt_common.mod
../definitions/mod_common.o: mdt_rd_common.mod mem_common.mod mkilst_common.mod
../definitions/mod_common.o: model2_common.mod model_common.mod
../definitions/mod_common.o: most_constants.mod numbers_constants.mod
../definitions/mod_common.o: opt_common.mod param_common.mod pdbnam_common.mod
../definitions/mod_common.o: pseudo_common.mod radii_common.mod
../definitions/mod_common.o: resgrp_common.mod residues_common.mod
../definitions/mod_common.o: residues_constants.mod restr_common.mod
../definitions/mod_common.o: restraint_constants.mod sched_common.mod
../definitions/mod_common.o: seqsearch_common.mod sigma_common.mod
../definitions/mod_common.o: special_common.mod sstruc_common.mod
../definitions/mod_common.o: string_constants.mod symmetry_common.mod
../definitions/mod_common.o: topol_common.mod types_constants.mod
../definitions/model2_common.o: most_constants.mod string_constants.mod
../definitions/model_common.o: most_constants.mod string_constants.mod
../definitions/opt_common.o: most_constants.mod
../definitions/param_common.o: most_constants.mod string_constants.mod
../definitions/pdbnam_common.o: string_constants.mod
../definitions/pseudo_common.o: most_constants.mod
../definitions/radii_common.o: most_constants.mod string_constants.mod
../definitions/resgrp_common.o: most_constants.mod string_constants.mod
../definitions/residues_common.o: residues_constants.mod string_constants.mod
../definitions/restr_common.o: most_constants.mod string_constants.mod
../definitions/sched_common.o: most_constants.mod string_constants.mod
../definitions/seqsearch_common.o: string_constants.mod
../definitions/sigma_common.o: most_constants.mod string_constants.mod
../definitions/sstruc_common.o: restraint_constants.mod string_constants.mod
../definitions/symmetry_common.o: most_constants.mod
../definitions/top_common.o: io_constants.mod most_constants.mod
../definitions/top_common.o: numbers_constants.mod restraint_constants.mod
../definitions/top_common.o: string_constants.mod
../definitions/topol_common.o: most_constants.mod residues_constants.mod
../definitions/topol_common.o: string_constants.mod
../main/mod.o: license.h
../main/mod.o: alloc_routines.mod  
../main/mod.o: io_routines.mod mod_common.mod sys_routines.mod top_common.mod
../main/mod.o: top_mod_routines.mod top_routines.mod
../main/top.o: algebra_routines.mod  io_constants.mod
../main/top.o: io_routines.mod numbers_constants.mod string_constants.mod
../main/top.o: string_routines.mod top_act_routines.mod top_mod_routines.mod
../main/top_act.o: add_restraint_module.mod align2d_module.mod
../main/top_act.o: align3d_module.mod align_consensus_module.mod
../main/top_act.o: align_module.mod alloc_routines.mod atom_residue_routines.mod
../main/top_act.o: build_model_module.mod check_alignment_module.mod
../main/top_act.o: check_routines.mod color_aln_model_module.mod
../main/top_act.o: compare_alignments_module.mod compare_module.mod
../main/top_act.o: compare_routines.mod condense_restraints_module.mod
../main/top_act.o: debug_function_module.mod define_symmetry_module.mod
../main/top_act.o: delete_alignment_module.mod delete_restraint_module.mod
../main/top_act.o: dendrogram_module.mod describe_module.mod
../main/top_act.o: dihedral_routines.mod energy_module.mod
../main/top_act.o: expand_alignment_module.mod generate_topology_module.mod
../main/top_act.o: getdata_routines.mod id_table_module.mod io_routines.mod
../main/top_act.o: iupac_model_module.mod make_region_module.mod
../main/top_act.o: make_restraints_module.mod make_schedule_module.mod
../main/top_act.o: make_topology_model_module.mod make_write_uhbd_lib_module.mod
../main/top_act.o: malign3d_module.mod malign_module.mod mod_common.mod
../main/top_act.o: mutate_model_module.mod optimize_module.mod
../main/top_act.o: orient_model_module.mod patch_module.mod
../main/top_act.o: patch_ss_model_module.mod patch_ss_templates_module.mod
../main/top_act.o: pick_atoms_module.mod pick_hot_atoms_module.mod
../main/top_act.o: pick_restraints_module.mod principal_components_module.mod
../main/top_act.o: psa_routines.mod pseudo_atoms_routines.mod
../main/top_act.o: quick_and_dirty_module.mod randomize_xyz_module.mod
../main/top_act.o: read_alignment_module.mod read_atom_classes_module.mod
../main/top_act.o: read_model_module.mod read_parameters_module.mod
../main/top_act.o: read_restraints_module.mod read_restyp_lib_module.mod
../main/top_act.o: read_schedule_module.mod read_topology_module.mod
../main/top_act.o: reindex_restraints_module.mod rename_segments_module.mod
../main/top_act.o: reorder_atoms_module.mod rotate_dihedrals_module.mod
../main/top_act.o: rotate_model_module.mod segment_matching_module.mod
../main/top_act.o: sequence_comparison_module.mod sequence_search_module.mod
../main/top_act.o: sequence_to_ali_module.mod spline_restraints_module.mod
../main/top_act.o: string_constants.mod string_routines.mod superpose_module.mod
../main/top_act.o: top_common.mod transfer_res_numb_module.mod
../main/top_act.o: transfer_xyz_module.mod unbuild_model_module.mod
../main/top_act.o: write_alignment_module.mod write_model_module.mod
../main/top_act.o: write_pdb_xref_module.mod write_restraints_module.mod
../main/top_act.o: write_schedule_module.mod write_topology_model_module.mod
../main/top_mod.o: algebra_routines.mod alloc_routines.mod
../main/top_mod.o: atom_residue_routines.mod check_routines.mod
../main/top_mod.o: delete_alignment_module.mod  io_constants.mod
../main/top_mod.o: io_routines.mod mod_common.mod most_constants.mod
../main/top_mod.o: ranlib_routines.mod rdlib_routines.mod
../main/top_mod.o: read_atom_classes_module.mod read_restyp_lib_module.mod
../main/top_mod.o: residues_constants.mod string_constants.mod
../main/top_mod.o: string_routines.mod sys_routines.mod top_common.mod
../routines/algebra_routines.o: io_routines.mod numbers_constants.mod
../routines/alloc_routines.o: ../include/alloc2.inc ../include/alloc1.inc
../routines/alloc_routines.o: ../include/alloc3.inc
../routines/alloc_routines.o: io_routines.mod mod_common.mod
../routines/alloc_routines.o: objective_function_routines.mod
../routines/alloc_routines.o: string_constants.mod string_routines.mod
../routines/alloc_routines.o: top_common.mod
../routines/atom_residue_routines.o: atmcls_common.mod charmm_constants.mod
../routines/atom_residue_routines.o: io_routines.mod radii_common.mod
../routines/atom_residue_routines.o: residues_common.mod special_common.mod
../routines/atom_residue_routines.o: string_constants.mod string_routines.mod
../routines/atom_residue_routines.o: topol_common.mod
../routines/cg_routines.o: algebra_routines.mod io_constants.mod io_routines.mod
../routines/cg_routines.o: numbers_constants.mod objective_function_routines.mod
../routines/cg_routines.o: string_constants.mod string_routines.mod
../routines/cg_routines.o: write_model_module.mod
../routines/check_routines.o: algebra_routines.mod atom_residue_routines.mod
../routines/check_routines.o: io_constants.mod io_routines.mod
../routines/check_routines.o: residues_common.mod string_constants.mod
../routines/check_routines.o: types_constants.mod
../routines/compare_routines.o: algebra_routines.mod atom_residue_routines.mod
../routines/compare_routines.o: io_constants.mod io_routines.mod
../routines/compare_routines.o: numbers_constants.mod rdlib_routines.mod
../routines/compare_routines.o: residues_common.mod residues_constants.mod
../routines/compare_routines.o: string_constants.mod string_routines.mod
../routines/dihedral_routines.o: algebra_routines.mod atom_residue_routines.mod
../routines/dihedral_routines.o: io_routines.mod most_constants.mod
../routines/dihedral_routines.o: numbers_constants.mod residues_common.mod
../routines/dihedral_routines.o: types_constants.mod
../routines/e_binormal.o: features_routines.mod numbers_constants.mod
../routines/e_cosine.o: features_routines.mod
../routines/e_coulomb.o: features_routines.mod switch_routines.mod
../routines/e_gauss.o: features_routines.mod numbers_constants.mod
../routines/e_harmonic.o: features_routines.mod numbers_constants.mod
../routines/e_lennard.o: features_routines.mod switch_routines.mod
../routines/e_spline.o: features_routines.mod
../routines/e_symmetry.o: algebra_routines.mod atom_residue_routines.mod
../routines/features_routines.o: algebra_routines.mod dihedral_routines.mod
../routines/features_routines.o: io_constants.mod io_routines.mod
../routines/features_routines.o: numbers_constants.mod restraint_routines.mod
../routines/finish_routine.o: alloc_routines.mod io_constants.mod
../routines/finish_routine.o: io_routines.mod sys_routines.mod top_common.mod
../routines/fit_routines.o: algebra_routines.mod atom_residue_routines.mod
../routines/fit_routines.o: io_routines.mod numbers_constants.mod
../routines/fit_routines.o: string_routines.mod
../routines/getdata_routines.o: algebra_routines.mod atom_residue_routines.mod
../routines/getdata_routines.o: check_routines.mod dihedral_routines.mod
../routines/getdata_routines.o: fit_routines.mod io_routines.mod mem_common.mod
../routines/getdata_routines.o: psa_routines.mod read_model_module.mod
../routines/getdata_routines.o: string_routines.mod
../routines/io_routines.o:   pdbnam_common.mod
../routines/io_routines.o: string_constants.mod string_routines.mod
../routines/io_routines.o: top_common.mod
../routines/md_routines.o: algebra_routines.mod cg_routines.mod io_routines.mod
../routines/md_routines.o: numbers_constants.mod objective_function_routines.mod
../routines/md_routines.o: ranlib_routines.mod string_routines.mod
../routines/mdt_routines.o: algebra_routines.mod atom_residue_routines.mod
../routines/mdt_routines.o: check_routines.mod compare_routines.mod
../routines/mdt_routines.o: dihedral_routines.mod getdata_routines.mod
../routines/mdt_routines.o: io_routines.mod numbers_constants.mod
../routines/mdt_routines.o: residues_common.mod string_routines.mod
../routines/mdt_routines.o: types_constants.mod
../routines/nonb_pairs_routines.o: atom_residue_routines.mod io_routines.mod
../routines/nonb_pairs_routines.o: restraint_routines.mod
../routines/nr_routines.o: io_routines.mod
../routines/objective_function_routines.o: algebra_routines.mod
../routines/objective_function_routines.o: check_routines.mod
../routines/objective_function_routines.o: e_binormal_module.mod
../routines/objective_function_routines.o: e_cosine_module.mod
../routines/objective_function_routines.o: e_coulomb_module.mod
../routines/objective_function_routines.o: e_gauss_module.mod
../routines/objective_function_routines.o: e_harmonic_module.mod
../routines/objective_function_routines.o: e_lennard_module.mod
../routines/objective_function_routines.o: e_spline_module.mod
../routines/objective_function_routines.o: e_symmetry_module.mod
../routines/objective_function_routines.o: features_routines.mod io_routines.mod
../routines/objective_function_routines.o: nonb_pairs_routines.mod
../routines/objective_function_routines.o: numbers_constants.mod
../routines/objective_function_routines.o: pseudo_atoms_routines.mod
../routines/objective_function_routines.o: rdlib_routines.mod
../routines/objective_function_routines.o: restraint_routines.mod
../routines/objective_function_routines.o: special_common.mod
../routines/objective_function_routines.o: types_constants.mod
../routines/psa_routines.o: atom_residue_routines.mod io_routines.mod
../routines/psa_routines.o: most_constants.mod numbers_constants.mod
../routines/psa_routines.o: string_routines.mod
../routines/pseudo_atoms_routines.o: algebra_routines.mod io_routines.mod
../routines/pseudo_atoms_routines.o: most_constants.mod numbers_constants.mod
../routines/r_alpha.o: algebra_routines.mod atom_residue_routines.mod
../routines/r_alpha.o: io_routines.mod restraint_constants.mod
../routines/r_alpha.o: restraint_routines.mod types_constants.mod
../routines/r_dihedral.o: algebra_routines.mod check_routines.mod
../routines/r_dihedral.o: mdt_routines.mod restraint_routines.mod
../routines/r_dihedral.o: spline_restraints_module.mod
../routines/r_distance.o: algebra_routines.mod atom_residue_routines.mod
../routines/r_distance.o: check_routines.mod compare_routines.mod
../routines/r_distance.o: getdata_routines.mod mdt_routines.mod
../routines/r_distance.o: residues_common.mod restraint_routines.mod
../routines/r_distance.o: spline_restraints_module.mod string_routines.mod
../routines/r_nonb_pair.o: algebra_routines.mod io_routines.mod
../routines/r_nonb_pair.o: restraint_routines.mod types_constants.mod
../routines/r_one_atom.o: io_routines.mod restraint_routines.mod
../routines/r_phi_psi_binormal.o: algebra_routines.mod check_routines.mod
../routines/r_phi_psi_binormal.o: io_routines.mod mdt_routines.mod
../routines/r_phi_psi_binormal.o: restraint_routines.mod
../routines/r_sheet.o: atom_residue_routines.mod io_routines.mod
../routines/r_sheet.o: restraint_constants.mod restraint_routines.mod
../routines/r_sheet.o: string_routines.mod types_constants.mod
../routines/r_stereo.o: algebra_routines.mod atom_residue_routines.mod
../routines/r_stereo.o: check_routines.mod features_routines.mod io_routines.mod
../routines/r_stereo.o: numbers_constants.mod residues_common.mod
../routines/r_stereo.o: restraint_routines.mod string_routines.mod
../routines/r_stereo.o: types_constants.mod
../routines/r_strand.o: algebra_routines.mod io_routines.mod residues_common.mod
../routines/r_strand.o: restraint_routines.mod types_constants.mod
../routines/r_user_distance.o: io_routines.mod restraint_routines.mod
../routines/rdlib_routines.o: algebra_routines.mod atom_residue_routines.mod
../routines/rdlib_routines.o: io_routines.mod most_constants.mod
../routines/rdlib_routines.o: numbers_constants.mod residues_common.mod
../routines/rdlib_routines.o: string_constants.mod string_routines.mod
../routines/restraint_routines.o: algebra_routines.mod check_routines.mod
../routines/restraint_routines.o: io_routines.mod mdt_routines.mod
../routines/restraint_routines.o: numbers_constants.mod special_common.mod
../routines/restraint_routines.o: types_constants.mod
../routines/string_routines.o: string_constants.mod
../routines/sys_routines.o:   io_constants.mod
../routines/sys_routines.o: io_routines.mod string_constants.mod
../routines/violations_routines.o: algebra_routines.mod e_binormal_module.mod
../routines/violations_routines.o: e_cosine_module.mod e_coulomb_module.mod
../routines/violations_routines.o: e_gauss_module.mod e_harmonic_module.mod
../routines/violations_routines.o: e_lennard_module.mod e_spline_module.mod
../routines/violations_routines.o: features_routines.mod io_routines.mod
../routines/violations_routines.o: numbers_constants.mod
../routines/violations_routines.o: objective_function_routines.mod
add_restraint_module.mod: ../commands/add_restraint.o
algebra_routines.mod: ../routines/algebra_routines.o
align2_common.mod: ../definitions/align2_common.o
align2d_module.mod: ../commands/align2d.o
align3d_module.mod: ../commands/align3d.o
align_common.mod: ../definitions/align_common.o
align_consensus_module.mod: ../commands/align_consensus.o
align_module.mod: ../commands/align.o
alignment_constants.mod: ../definitions/alignment_constants.o
alloc_routines.mod: ../routines/alloc_routines.o
atmcls_common.mod: ../definitions/atmcls_common.o
atom_residue_routines.mod: ../routines/atom_residue_routines.o
build_model_module.mod: ../commands/build_model.o
cg_routines.mod: ../routines/cg_routines.o
charmm_constants.mod: ../definitions/charmm_constants.o
check_alignment_module.mod: ../commands/check_alignment.o
check_routines.mod: ../routines/check_routines.o
cmpcsr_common.mod: ../definitions/cmpcsr_common.o
color_aln_model_module.mod: ../commands/color_aln_model.o
compare_alignments_module.mod: ../commands/compare_alignments.o
compare_module.mod: ../commands/compare.o
compare_routines.mod: ../routines/compare_routines.o
condense_restraints_module.mod: ../commands/condense_restraints.o
debug_function_module.mod: ../commands/debug_function.o
define_symmetry_module.mod: ../commands/define_symmetry.o
delete_alignment_module.mod: ../commands/delete_alignment.o
delete_restraint_module.mod: ../commands/delete_restraint.o
dendrogram_module.mod: ../commands/dendrogram.o
describe_module.mod: ../commands/describe.o
dihedral_routines.mod: ../routines/dihedral_routines.o
dihlib_common.mod: ../definitions/dihlib_common.o
dynamic_allocation_common.mod: ../definitions/dynamic_allocation_common.o
e_binormal_module.mod: ../routines/e_binormal.o
e_cosine_module.mod: ../routines/e_cosine.o
e_coulomb_module.mod: ../routines/e_coulomb.o
e_gauss_module.mod: ../routines/e_gauss.o
e_harmonic_module.mod: ../routines/e_harmonic.o
e_lennard_module.mod: ../routines/e_lennard.o
e_spline_module.mod: ../routines/e_spline.o
e_symmetry_module.mod: ../routines/e_symmetry.o
energy_module.mod: ../commands/energy.o
equiv_common.mod: ../definitions/equiv_common.o
expand_alignment_module.mod: ../commands/expand_alignment.o
features_routines.mod: ../routines/features_routines.o
fit_routines.mod: ../routines/fit_routines.o
generate_topology_module.mod: ../commands/generate_topology.o
getdata_routines.mod: ../routines/getdata_routines.o
hash_common.mod: ../definitions/hash_common.o
id_table_module.mod: ../commands/id_table.o
intcr_common.mod: ../definitions/intcr_common.o
io_constants.mod: ../definitions/io_constants.o
io_routines.mod: ../routines/io_routines.o
iupac_model_module.mod: ../commands/iupac_model.o
lib_common.mod: ../definitions/lib_common.o
make_region_module.mod: ../commands/make_region.o
make_restraints_module.mod: ../commands/make_restraints.o
make_schedule_module.mod: ../commands/make_schedule.o
make_topology_model_module.mod: ../commands/make_topology_model.o
make_write_uhbd_lib_module.mod: ../commands/make_write_uhbd_lib.o
malign3d_module.mod: ../commands/malign3d.o
malign_module.mod: ../commands/malign.o
md_common.mod: ../definitions/md_common.o
md_routines.mod: ../routines/md_routines.o
mdt_dat_common.mod: ../definitions/mdt_dat_common.o
mdt_mdt_common.mod: ../definitions/mdt_mdt_common.o
mdt_rd_common.mod: ../definitions/mdt_rd_common.o
mdt_routines.mod: ../routines/mdt_routines.o
mem_common.mod: ../definitions/mem_common.o
mkilst_common.mod: ../definitions/mkilst_common.o
mod_common.mod: ../definitions/mod_common.o
model2_common.mod: ../definitions/model2_common.o
model_common.mod: ../definitions/model_common.o
most_constants.mod: ../definitions/most_constants.o
mutate_model_module.mod: ../commands/mutate_model.o
nonb_pairs_routines.mod: ../routines/nonb_pairs_routines.o
nr_routines.mod: ../routines/nr_routines.o
numbers_constants.mod: ../definitions/numbers_constants.o
objective_function_routines.mod: ../routines/objective_function_routines.o
opt_common.mod: ../definitions/opt_common.o
optimize_module.mod: ../commands/optimize.o
orient_model_module.mod: ../commands/orient_model.o
param_common.mod: ../definitions/param_common.o
patch_module.mod: ../commands/patch.o
patch_ss_model_module.mod: ../commands/patch_ss_model.o
patch_ss_templates_module.mod: ../commands/patch_ss_templates.o
pdbnam_common.mod: ../definitions/pdbnam_common.o
pick_atoms_module.mod: ../commands/pick_atoms.o
pick_hot_atoms_module.mod: ../commands/pick_hot_atoms.o
pick_restraints_module.mod: ../commands/pick_restraints.o
principal_components_module.mod: ../commands/principal_components.o
psa_routines.mod: ../routines/psa_routines.o
pseudo_atoms_routines.mod: ../routines/pseudo_atoms_routines.o
pseudo_common.mod: ../definitions/pseudo_common.o
quick_and_dirty_module.mod: ../commands/quick_and_dirty.o
r_alpha_module.mod: ../routines/r_alpha.o
r_dihedral_module.mod: ../routines/r_dihedral.o
r_distance_module.mod: ../routines/r_distance.o
r_nonb_pair_module.mod: ../routines/r_nonb_pair.o
r_one_atom_module.mod: ../routines/r_one_atom.o
r_phi_psi_binormal_module.mod: ../routines/r_phi_psi_binormal.o
r_sheet_module.mod: ../routines/r_sheet.o
r_stereo_module.mod: ../routines/r_stereo.o
r_strand_module.mod: ../routines/r_strand.o
r_user_distance_module.mod: ../routines/r_user_distance.o
radii_common.mod: ../definitions/radii_common.o
randomize_xyz_module.mod: ../commands/randomize_xyz.o
ranlib_routines.mod: ../routines/ranlib_routines.o
rdlib_routines.mod: ../routines/rdlib_routines.o
read_alignment_module.mod: ../commands/read_alignment.o
read_atom_classes_module.mod: ../commands/read_atom_classes.o
read_model_module.mod: ../commands/read_model.o
read_parameters_module.mod: ../commands/read_parameters.o
read_restraints_module.mod: ../commands/read_restraints.o
read_restyp_lib_module.mod: ../commands/read_restyp_lib.o
read_schedule_module.mod: ../commands/read_schedule.o
read_topology_module.mod: ../commands/read_topology.o
reindex_restraints_module.mod: ../commands/reindex_restraints.o
rename_segments_module.mod: ../commands/rename_segments.o
reorder_atoms_module.mod: ../commands/reorder_atoms.o
resgrp_common.mod: ../definitions/resgrp_common.o
residues_common.mod: ../definitions/residues_common.o
residues_constants.mod: ../definitions/residues_constants.o
restr_common.mod: ../definitions/restr_common.o
restraint_constants.mod: ../definitions/restraint_constants.o
restraint_routines.mod: ../routines/restraint_routines.o
rotate_dihedrals_module.mod: ../commands/rotate_dihedrals.o
rotate_model_module.mod: ../commands/rotate_model.o
sched_common.mod: ../definitions/sched_common.o
segment_matching_module.mod: ../commands/segment_matching.o
seqsearch_common.mod: ../definitions/seqsearch_common.o
sequence_comparison_module.mod: ../commands/sequence_comparison.o
sequence_search_module.mod: ../commands/sequence_search.o
sequence_to_ali_module.mod: ../commands/sequence_to_ali.o
sigma_common.mod: ../definitions/sigma_common.o
special_common.mod: ../definitions/special_common.o
spline_restraints_module.mod: ../commands/spline_restraints.o
sstruc_common.mod: ../definitions/sstruc_common.o
string_constants.mod: ../definitions/string_constants.o
string_routines.mod: ../routines/string_routines.o
superpose_module.mod: ../commands/superpose.o
switch_routines.mod: ../routines/switch_routines.o
symmetry_common.mod: ../definitions/symmetry_common.o
sys_routines.mod: ../routines/sys_routines.o
top_act_routines.mod: ../main/top_act.o
top_common.mod: ../definitions/top_common.o
top_mod_routines.mod: ../main/top_mod.o
top_routines.mod: ../main/top.o
topol_common.mod: ../definitions/topol_common.o
transfer_res_numb_module.mod: ../commands/transfer_res_numb.o
transfer_xyz_module.mod: ../commands/transfer_xyz.o
types_constants.mod: ../definitions/types_constants.o
unbuild_model_module.mod: ../commands/unbuild_model.o
violations_routines.mod: ../routines/violations_routines.o
write_alignment_module.mod: ../commands/write_alignment.o
write_model_module.mod: ../commands/write_model.o
write_pdb_xref_module.mod: ../commands/write_pdb_xref.o
write_restraints_module.mod: ../commands/write_restraints.o
write_schedule_module.mod: ../commands/write_schedule.o
write_topology_model_module.mod: ../commands/write_topology_model.o
