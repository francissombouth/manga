import React, { useState, useEffect } from 'react';
import { Link as RouterLink } from 'react-router-dom';
import {
  Container,
  Grid,
  Card,
  CardContent,
  CardMedia,
  Typography,
  TextField,
  Box,
  Chip,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Pagination,
} from '@mui/material';
import axios from 'axios';

function Oeuvres() {
  const [oeuvres, setOeuvres] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');
  const [filters, setFilters] = useState({
    auteur: '',
    tag: '',
    statut: '',
  });
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [auteurs, setAuteurs] = useState([]);
  const [tags, setTags] = useState([]);
  const [statuts, setStatuts] = useState([]);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [oeuvresRes, auteursRes, tagsRes, statutsRes] = await Promise.all([
          axios.get('/oeuvres', {
            params: {
              page,
              search,
              ...filters,
            },
          }),
          axios.get('/auteurs'),
          axios.get('/tags'),
          axios.get('/statuts'),
        ]);

        setOeuvres(oeuvresRes.data['hydra:member']);
        setTotalPages(Math.ceil(oeuvresRes.data['hydra:totalItems'] / 12));
        setAuteurs(auteursRes.data['hydra:member']);
        setTags(tagsRes.data['hydra:member']);
        setStatuts(statutsRes.data['hydra:member']);
      } catch (error) {
        setError('Erreur lors du chargement des données');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [page, search, filters]);

  const handleFilterChange = (event) => {
    const { name, value } = event.target;
    setFilters((prev) => ({
      ...prev,
      [name]: value,
    }));
    setPage(1);
  };

  const handleSearchChange = (event) => {
    setSearch(event.target.value);
    setPage(1);
  };

  const handlePageChange = (event, value) => {
    setPage(value);
  };

  if (loading) {
    return (
      <Container>
        <Typography>Chargement...</Typography>
      </Container>
    );
  }

  if (error) {
    return (
      <Container>
        <Typography color="error">{error}</Typography>
      </Container>
    );
  }

  return (
    <Container>
      <Box sx={{ my: 4 }}>
        <Typography variant="h4" component="h1" gutterBottom>
          Œuvres
        </Typography>

        <Grid container spacing={2} sx={{ mb: 4 }}>
          <Grid item xs={12} md={4}>
            <TextField
              fullWidth
              label="Rechercher"
              value={search}
              onChange={handleSearchChange}
            />
          </Grid>
          <Grid item xs={12} md={4}>
            <FormControl fullWidth>
              <InputLabel>Auteur</InputLabel>
              <Select
                name="auteur"
                value={filters.auteur}
                onChange={handleFilterChange}
                label="Auteur"
              >
                <MenuItem value="">Tous</MenuItem>
                {auteurs.map((auteur) => (
                  <MenuItem key={auteur.id} value={auteur.id}>
                    {auteur.nom} {auteur.prenom}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} md={4}>
            <FormControl fullWidth>
              <InputLabel>Tag</InputLabel>
              <Select
                name="tag"
                value={filters.tag}
                onChange={handleFilterChange}
                label="Tag"
              >
                <MenuItem value="">Tous</MenuItem>
                {tags.map((tag) => (
                  <MenuItem key={tag.id} value={tag.id}>
                    {tag.nom}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>
        </Grid>

        <Grid container spacing={4}>
          {oeuvres.map((oeuvre) => (
            <Grid item key={oeuvre.id} xs={12} sm={6} md={4}>
              <Card
                component={RouterLink}
                to={`/oeuvres/${oeuvre.id}`}
                sx={{
                  height: '100%',
                  display: 'flex',
                  flexDirection: 'column',
                  textDecoration: 'none',
                }}
              >
                <CardMedia
                  component="img"
                  height="200"
                  image={oeuvre.image || '/placeholder.jpg'}
                  alt={oeuvre.titre}
                />
                <CardContent>
                  <Typography gutterBottom variant="h5" component="h2">
                    {oeuvre.titre}
                  </Typography>
                  <Typography
                    variant="body2"
                    color="text.secondary"
                    gutterBottom
                  >
                    {oeuvre.auteur.nom} {oeuvre.auteur.prenom}
                  </Typography>
                  <Box sx={{ mt: 1 }}>
                    {oeuvre.tags.map((tag) => (
                      <Chip
                        key={tag.id}
                        label={tag.nom}
                        size="small"
                        sx={{ mr: 0.5, mb: 0.5 }}
                      />
                    ))}
                  </Box>
                </CardContent>
              </Card>
            </Grid>
          ))}
        </Grid>

        <Box sx={{ mt: 4, display: 'flex', justifyContent: 'center' }}>
          <Pagination
            count={totalPages}
            page={page}
            onChange={handlePageChange}
            color="primary"
          />
        </Box>
      </Box>
    </Container>
  );
}

export default Oeuvres; 